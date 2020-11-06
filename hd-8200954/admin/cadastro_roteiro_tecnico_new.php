<?php
use html\HtmlBuilder;
use model\ModelHolder;

$edit = false;

if(!empty($_GET["roteiro"])){

    $roteiro = getRoteiro1($_GET["roteiro"]);
    $roteiro = $roteiro[$_GET["roteiro"]];

    $statusRoteiro = getLegendaByIdStatus($roteiro["status_roteiro"]);
    $roteiro["tecnicoid"] = $roteiro["tecnicoid"];
    $edit = $_GET["edit"];
}

if ($_POST["ajax_remove_agenda_visita"] == true ) {

    $modelRoteiro = ModelHolder::init("RoteiroPosto");      

    if($modelRoteiro->delete($_POST["id"])){
        exit(json_encode(array('success' => "1")));
    }else{
        exit(json_encode(array('success' => "0")));
    }

}

if ($_GET["externo"] == true && strlen($_GET['posto']) > 0) {

    $xposto = $_GET['posto'];
    $sql = "SELECT tbl_posto.cnpj, 
                    tbl_posto_fabrica.cod_ibge,
                   tbl_posto_fabrica.contato_estado, 
                   tbl_posto_fabrica.contato_cidade
              FROM tbl_posto_fabrica
              JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
             WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
               AND tbl_posto_fabrica.fabrica = {$login_fabrica} 
               AND tbl_posto_fabrica.posto = {$xposto} ";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        $_POST['posto'][]             = pg_fetch_result($res, 0, 'cnpj');
        $roteiro["data_inicio"]       = date("d/m/Y");
        $roteiro["data_termino"]      = date('d/m/Y', strtotime('+1 days'));
        $roteiro["tipo_roteiro"]      = 'RT';
        $_POST['qtde_horas_postos'][] = '01:00';
        $_POST['data_visita_postos'][] = date("d/m/Y");
        $_POST['tipo_visita_posto'][] = 'VT';
        $_POST['tipo_contato_posto'][] = 'PA';
        $_POST['estado'][]            = pg_fetch_result($res, 0, 'contato_estado');
        $_POST["cidade"][]            = pg_fetch_result($res, 0, 'cod_ibge');
        $roteiro['roteiro']           = "";
        $roteiro["tecnico"]            = $_POST["tecnico"];
    }

}

if(isset($_POST["action"]) && $_POST["action"] == "deleteRoteiro" && !empty($_POST["roteiro"])){
    $modelRoteiro = ModelHolder::init("Roteiro");       

    if($modelRoteiro->delete($_POST["roteiro"]) > 0 ){
        die(json_encode(array('success' => "true" )));
    }else{
        die(json_encode(array('success' => "false" )));
    }
    
}

if(isset($_POST["action"]) && $_POST["action"] == "getEstados" && !empty($_POST["estado"])){
    
    $result = "<select id='cidade' name='cidade[]' multiple='multiple' class='span12' >";
    $result .= "<option value=''></option>";

    $get_cidades = getCidades1($_POST["estado"]);
    if (!empty($_POST["arraicidade"])) {
        $cidades = str_replace("\\", "", $_POST["arraicidade"]);
        $cidades = json_decode($cidades,true);
    }else{
        $cidades = array();
    }

    if (!empty($_POST["array_posto_cidade"])) {
        $cidades_p = str_replace("\\", "", $_POST["array_posto_cidade"]);       
        $cidades_p = json_decode($cidades_p,true);
    }

    foreach ($get_cidades as $key => $value) {
        $selected = ""; 

        if (!empty($cidades)) {
            $selected = (in_array($value['cod_ibge'], $cidades)) ? "SELECTED":"";
        }
        if (!empty($cidades_p)) {
            foreach ($cidades_p as $key => $conteudo) {
                $sqlRoteiroPosto = "SELECT posto,cidade FROM tbl_roteiro_posto WHERE roteiro_posto = {$key};";
                $resRoteiroPosto = pg_query($con,$sqlRoteiroPosto);             

                if (pg_num_rows($resRoteiroPosto) > 0) {
                    $postoRoteiro = pg_fetch_result($resRoteiroPosto, 0, posto);
                    $cidadeRoteiro = pg_fetch_result($resRoteiroPosto, 0, cidade);
                    if (!empty($postoRoteiro) ) {

                        //pesquiso pelo estado do posto
                        $sqlPostoFabrica = "SELECT cod_ibge 
                                                FROM tbl_posto_fabrica                                              
                                                WHERE fabrica = {$login_fabrica} AND posto = {$postoRoteiro}";
                        $resPostoFabrica = pg_query($con,$sqlPostoFabrica);

                        $estadoPosto = pg_fetch_result($resPostoFabrica, 0, cod_ibge);
                        
                        if ($value['cod_ibge'] == $estadoPosto) {
                
                            $selected = "SELECTED";                                             
                        }
                    }
                    if (!empty($cidadeRoteiro)) {
                        //pesquiso pelo estado da cidade
                        $sqlCidade = "SELECT cod_ibge FROM tbl_cidade WHERE cidade = {$cidadeRoteiro}";
                        $resCidade = pg_query($con,$sqlCidade);
                        $estadoCidade = pg_fetch_result($resCidade, 0, cod_ibge);
                
                        if ($value['cod_ibge'] == $estadoCidade) {
                
                            $selected = "SELECTED";                                             
                        }
                    }
                }                                       
            }           
        }
        $result .= "<option value='".$value['cod_ibge']."'".$selected.">".$value['cidade']."</option>";
    }

    $result .= "</select>";
    echo $result;
    exit;
}

if(isset($_POST["btn_acao"]) && $_POST["btn_acao"] == "save"){

    $roteiro = array();
    try{

        $validation                 = validateFields1();
        $dataInicio                 = DateTime::createFromFormat("d/m/Y", $_POST["data_inicio"]);
        $dataTermino                = DateTime::createFromFormat("d/m/Y", $_POST["data_termino"]);
        $roteiro["roteiro"]         = (empty($_POST["roteiro"])) ? NULL : $_POST['roteiro'];
        $roteiro["data_inicio"]     = $_POST["data_inicio"];
        $roteiro["data_termino"]    = $_POST["data_termino"];       
        $roteiro["tipo_roteiro"]    = $_POST["tipo_roteiro"];
        $roteiro["solicitante"]     = substr($_POST["solicitante"], 0, 20);
        $roteiro["qtde_dias"]       = $_POST['qtde_dias'];
        $roteiro["excecoes"]        = $_POST["excecoes"];
        $roteiro["ativo"]           = $_POST["ativo"];
        $roteiro["status_roteiro"]  = getLegendaByStatus($_POST["status_roteiro"]);
        $roteiro["fabrica"]         = $login_fabrica;
        $list["estado"]             = $_POST["estado"];
        $list["cidade"]             = $_POST["cidade"];
        
        $list["tecnicos"]           = $_POST["tecnico"];
        $tipo_visita                = $_POST["tipo_visita"];
        $tipo_contato               = $_POST["tipo_contato"];
        $qtde_horas_posto           = $_POST["qtde_horas_posto"];
        $statusRoteiro              = $_POST["status_roteiro"];

        $tempUniqueId              = $_POST["tempUniqueId"];
        $temHash                   = $_POST["temHash"];


        for ($i=0; $i < count($_POST["tipo_contato_posto"]) ; $i++) { 
            if ($_POST["tipo_contato_posto"][$i] == "PA") {
                $list["postos"][$i] = $_POST["posto"][$i];
            }
            if ($_POST["tipo_contato_posto"][$i] == "CL") {
                $list["clientes"][$i] = $_POST["posto"][$i];
            }
            if ($_POST["tipo_contato_posto"][$i] == "RV") {
                $list["revendas"][$i] = $_POST["posto"][$i];
            }
        }

        if (count($list["postos"]) > 0) {

            $postos_fabrica = "'".implode("','", $list["postos"])."'";

            //pegar as cidades do posto
            $sql_posto = "SELECT tbl_cidade.cod_ibge
                            FROM tbl_posto_fabrica
                            JOIN tbl_cidade on tbl_posto_fabrica.contato_cidade = tbl_cidade.nome
                           WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                             AND tbl_posto_fabrica.codigo_posto in ({$postos_fabrica})";
            $res_posto = pg_query($con,$sql_posto);
            
            // verificar se existe a cidade do posto no array de cidades, se existir excluir do array
            if(pg_num_rows($res_posto) > 0){
                for ($i=0; $i < pg_num_rows($res_posto); $i++) { 
                    $cod_cidade_posto = pg_fetch_result($res_posto, $i, cod_ibge);
                    if (in_array($cod_cidade_posto, $list['cidade'])) {
                        
                        $excluir_cidade=array_search($cod_cidade_posto,$list['cidade']);
                        unset($list['cidade'][$excluir_cidade]);

                    }
                }
            }
        }

        if(empty($validation)){


            $dataInicio                 = DateTime::createFromFormat("d/m/Y", $_POST["data_inicio"]);
            $dataTermino                = DateTime::createFromFormat("d/m/Y", $_POST["data_termino"]);
            $roteiro["data_inicio"]     = $dataInicio->format(DateTime::ISO8601);
            $roteiro["data_termino"]    = $dataTermino->format(DateTime::ISO8601);

                $roteiro["admin"]   = $login_admin;
                $tecnico    = $_POST["tecnico"];
            
                $auditorLog = new AuditorLog();
                $sqlLog = "SELECT
                                tbl_roteiro.roteiro,
                                tbl_roteiro.data_inicio,
                                tbl_roteiro.data_termino,
                                tbl_roteiro.status_roteiro,
                                tbl_roteiro.solicitante,
                                tbl_roteiro.qtde_dias,
                                tbl_roteiro.excecoes AS comentarios,
                                tbl_tecnico.tecnico || ' - ' || tbl_tecnico.nome AS responsavel_tecnico,
                                tbl_admin.admin || ' - ' || tbl_admin.nome_completo AS responsavel_agendamento,
                                tbl_cidade.nome AS cidade,
                                tbl_cidade.estado AS estado,
                                tbl_roteiro_posto.qtde_horas,
                                tbl_roteiro_posto.data_visita,
                                CASE WHEN tbl_roteiro_posto.tipo_de_visita = 'VA' THEN 'Visita Admistrativa' 
                                     WHEN tbl_roteiro_posto.tipo_de_visita = 'VC' THEN 'Visita Comercial' 
                                     WHEN tbl_roteiro_posto.tipo_de_visita = 'VT' THEN 'Visita Técnica'
                                     WHEN tbl_roteiro_posto.tipo_de_visita = 'CM' THEN 'Clínica Makita'
                                     WHEN tbl_roteiro_posto.tipo_de_visita = 'FE' THEN 'Feira/Evento'
                                     WHEN tbl_roteiro_posto.tipo_de_visita = 'TN' THEN 'Treinamento'
                                     END AS tipo_visita,
                                CASE WHEN tbl_roteiro_posto.tipo_de_local = 'CL' THEN 'Cliente' 
                                     WHEN tbl_roteiro_posto.tipo_de_local = 'RV' THEN 'Revenda' 
                                     WHEN tbl_roteiro_posto.tipo_de_local = 'PA' THEN 'Posto Autorizado'
                                     END AS tipo_contato,
                                CASE WHEN tbl_roteiro_posto.tipo_de_local = 'CL' THEN tbl_cliente.cpf || ' - ' || tbl_cliente.nome 
                                     WHEN tbl_roteiro_posto.tipo_de_local = 'RV' THEN tbl_revenda.cnpj || ' - ' || tbl_revenda.nome
                                     WHEN tbl_roteiro_posto.tipo_de_local = 'PA' THEN tbl_posto.cnpj || ' - ' || tbl_posto.nome
                                     END AS nome_contato,
                                tbl_roteiro_posto.contato
                         FROM tbl_roteiro
                         JOIN tbl_roteiro_posto         ON tbl_roteiro_posto.roteiro    = tbl_roteiro.roteiro
                         JOIN tbl_roteiro_tecnico       ON tbl_roteiro_tecnico.roteiro    = tbl_roteiro.roteiro
                         JOIN tbl_tecnico            ON tbl_tecnico.tecnico              = tbl_roteiro_tecnico.tecnico         AND tbl_tecnico.fabrica = $login_fabrica
                         LEFT JOIN tbl_admin            ON tbl_admin.admin              = tbl_roteiro.admin         AND tbl_admin.fabrica = $login_fabrica
                         LEFT JOIN tbl_revenda          ON tbl_revenda.cnpj             = tbl_roteiro_posto.codigo
                         LEFT JOIN tbl_cliente          ON tbl_cliente.cpf              = tbl_roteiro_posto.codigo
                         LEFT JOIN tbl_posto            ON tbl_posto.cnpj               = tbl_roteiro_posto.codigo
                         LEFT JOIN tbl_posto_fabrica    ON tbl_posto_fabrica.posto      = tbl_posto.posto           AND tbl_posto_fabrica.fabrica = $login_fabrica
                         LEFT JOIN tbl_cidade           ON tbl_cidade.cidade            = tbl_roteiro_posto.cidade
                        WHERE tbl_roteiro.fabrica = {$login_fabrica}";
                $auditorLog->retornaDadosSelect($sqlLog);
                if(empty($roteiro["roteiro"])){
                    unset($roteiro["roteiro"]);
                    $modelRoteiro   = ModelHolder::init("Roteiro");
                    $roteiroId      = $modelRoteiro->insert($roteiro);
                    $acao = "INSERT";

                } else{             
                    $roteiroId = $roteiro["roteiro"];
                    //unset($roteiro["roteiro"]);
                    $modelRoteiro = ModelHolder::init("Roteiro");
                    $modelRoteiro->update($roteiro, $roteiroId);
                    $acao = "UPDATE";

                    deletar_tabelas2("tbl_roteiro_tecnico",$roteiroId);
                }

            if ($temHash) {
                $sqlTdoc = "SELECT tdocs_id
                              FROM tbl_tdocs 
                             WHERE hash_temp = '$tempUniqueId'";
                $resTdoc = pg_query($con, $sqlTdoc);
                if (pg_num_rows($resTdoc) > 0) {
                    foreach (pg_fetch_all($resTdoc) as $indice => $rows) {
                        $sqlTdocUp = "UPDATE  tbl_tdocs 
                                       SET referencia_id={$roteiroId}, hash_temp=NULL 
                                     WHERE tdocs_id = '".$rows["tdocs_id"]."'
                                       AND fabrica = {$login_fabrica}";
                        $resTdocUp = pg_query($con, $sqlTdocUp);

                    }
                }
            }

            //Inicio List
            //update list postos
            if (count($list["postos"]) > 0) {
                foreach ($list["postos"] as $key => $rows) {
                    //pegar o id do posto
                    //$modelPostoFabrica = ModelHolder::init("PostoFabrica");
                    //$posto = $modelPostoFabrica->find(array("codigo_posto" => $rows, 
                                                              //"fabrica"=>$login_fabrica), array("cnpj"));

                    $assunto_contato = [];
                    $assunto_array = "";

                    $postoId      = $rows;
                    $tipoContato  = $_POST["tipo_contato_posto"][$key];
                    $tipoVisita   = $_POST["tipo_visita_posto"][$key];
                    $dataVisita   = $_POST["data_visita_postos"][$key];
                    $tipoHoras    = $_POST["qtde_horas_postos"][$key];
                    if (strlen($_POST["qtde_horas_postos"][$key]) == 1) {
                        $tipoHoras = "0".$_POST["qtde_horas_postos"][$key].":00";
                    } else if (strlen($_POST["qtde_horas_postos"][$key]) == 2) {
                        $tipoHoras = $_POST["qtde_horas_postos"][$key].":00";
                    }

                    if (!empty($_POST["assunto"][$key])) {
                        $assunto_contato[$key]["assunto"] = $_POST["assunto"][$key];

                        if (!empty($_POST["assunto_add"][$key])) {
                            $assunto_contato[$key]["assunto_add"] = utf8_encode($_POST["assunto_add"][$key]);
                        }

                        $assunto_array = json_encode($assunto_contato);
                        $assunto_array = str_replace('\\u', '\\\\u', $assunto_array);

                        $dadosSave = array(
                                    "roteiro"        => $roteiroId, 
                                    "qtde_horas"     => $tipoHoras,
                                    "tipo_de_local"  => $tipoContato,
                                    "codigo"         => $postoId,
                                    "tipo_de_visita" => $tipoVisita,
                                    "data_visita"    => geraDataBD($dataVisita),
                                    "status"         => $statusRoteiro,
                                    "contato"        => $assunto_array,
                                );

                    } else {

                        $dadosSave = array(
                                        "roteiro"        => $roteiroId, 
                                        "qtde_horas"     => $tipoHoras,
                                        "tipo_de_local"  => $tipoContato,
                                        "codigo"         => $postoId,
                                        "tipo_de_visita" => $tipoVisita,
                                        "data_visita"    => geraDataBD($dataVisita),
                                        "status"         => $statusRoteiro,
                                    );

                    }


                    //verifica se o posto existe na tabela para este roteiro
                    $modelRoteiroPosto = ModelHolder::init("RoteiroPosto");                 
                    $roteiroPosto = $modelRoteiroPosto->find(array("roteiro" => $roteiroId,
                                                                    "tipo_de_local" => $tipoContato,
                                                                    "tipo_de_visita" => $tipoVisita,
                                                                    "codigo" => $postoId), array("roteiro_posto"));
                    $roteiroPostoId = $roteiroPosto[0]["roteiroPosto"];
                    if (empty($roteiroPostoId)) {
                        //insere
                        $modelRoteiroPosto->insert($dadosSave);

                    }else{
                        //update
                        $modelRoteiroPosto->update($dadosSave, $roteiroPostoId);
                    }
                    $auditorLog->retornaDadosSelect($sqlLog)->enviarLog($acao, 'tbl_roteiro', $login_fabrica.'*'.$roteiroId);

                }
            }
            //Inicio List
            //update list clientes
            if (count($list["clientes"]) > 0) {
                foreach ($list["clientes"] as $key => $rows) {
                    //pegar o id do cliente

                    $assunto_contato = [];
                    $assunto_array = "";

                    $modelCliente = ModelHolder::init("Cliente");
                    $cliente      = $modelCliente->find(array("cpf" => $rows), array("cpf"));
                    $clienteId    = $cliente[0]["cpf"];
                    $tipoContato  = $_POST["tipo_contato_posto"][$key];
                    $tipoVisita   = $_POST["tipo_visita_posto"][$key];
                    $tipoHoras    = $_POST["qtde_horas_postos"][$key];
                    if (strlen($_POST["qtde_horas_postos"][$key]) == 1) {
                        $tipoHoras = "0".$_POST["qtde_horas_postos"][$key].":00";
                    } else if (strlen($_POST["qtde_horas_postos"][$key]) == 2) {
                        $tipoHoras = $_POST["qtde_horas_postos"][$key].":00";
                    }
                    
                    $dataVisita   = $_POST["data_visita_postos"][$key];

                    if (!empty($_POST["assunto"][$key])) {
                        $assunto_contato[$key]["assunto"] = $_POST["assunto"][$key];

                        if (!empty($_POST["assunto_add"][$key])) {
                            $assunto_contato[$key]["assunto_add"] = utf8_encode($_POST["assunto_add"][$key]);
                        }

                        $assunto_array = json_encode($assunto_contato);
                        $assunto_array = str_replace('\\u', '\\\\u', $assunto_array);

                        $dadosSave = array(
                                    "roteiro"        => $roteiroId, 
                                    "qtde_horas"     => $tipoHoras,
                                    "tipo_de_local"  => $tipoContato,
                                    "codigo"         => $clienteId,
                                    "tipo_de_visita" => $tipoVisita,
                                    "data_visita"    => geraDataBD($dataVisita),
                                    "status"         => $statusRoteiro,
                                    "contato"        => $assunto_array,
                                );

                    } else {

                        $dadosSave = array(
                                    "roteiro"        => $roteiroId, 
                                    "qtde_horas"     => $tipoHoras,
                                    "tipo_de_local"  => $tipoContato,
                                    "codigo"         => $clienteId,
                                    "tipo_de_visita" => $tipoVisita,
                                    "data_visita"    => geraDataBD($dataVisita),
                                    "status"         => $statusRoteiro,
                                );

                    }
                    
                    //verifica se o posto existe na tabela para este roteiro
                    $modelRoteiroPosto = ModelHolder::init("RoteiroPosto");                 
                    $roteiroPosto = $modelRoteiroPosto->find(array("roteiro" => $roteiroId,
                                                                    "tipo_de_local" => $tipoContato,
                                                                    "codigo" => $clienteId), array("roteiro_posto"));
                    $roteiroPostoId = $roteiroPosto[0]["roteiroPosto"];
                    
                    if (empty($roteiroPostoId)) {
                        //insere
                        $modelRoteiroPosto->insert($dadosSave);
                    }else{
                        //update
                        $modelRoteiroPosto->update($dadosSave, $roteiroPostoId);
                    }
                    $auditorLog->retornaDadosSelect($sqlLog)->enviarLog($acao, 'tbl_roteiro', $login_fabrica.'*'.$roteiroId);
                }
            }
            //Inicio List
            //update list revendas
            if (count($list["revendas"]) > 0) {
                foreach ($list["revendas"] as $key => $rows) {
                    //pegar o id do revendas

                    $assunto_contato = [];
                    $assunto_array = "";

                    $modelRevenda = ModelHolder::init("Revenda");
                    $revenda      = $modelRevenda->find(array("cnpj" => $rows), array("cnpj"));
                    $revendaId    = $revenda[0]["cnpj"];
                    $tipoContato  = $_POST["tipo_contato_posto"][$key];
                    $tipoVisita   = $_POST["tipo_visita_posto"][$key];
                    $tipoHoras    = $_POST["qtde_horas_postos"][$key];
                    if (strlen($_POST["qtde_horas_postos"][$key]) == 1) {
                        $tipoHoras = "0".$_POST["qtde_horas_postos"][$key].":00";
                    } else if (strlen($_POST["qtde_horas_postos"][$key]) == 2) {
                        $tipoHoras = $_POST["qtde_horas_postos"][$key].":00";
                    }
                    $dataVisita   = $_POST["data_visita_postos"][$key];

                    if (!empty($_POST["assunto"][$key])) {
                        $assunto_contato[$key]["assunto"] = $_POST["assunto"][$key];

                        if (!empty($_POST["assunto_add"][$key])) {
                            $assunto_contato[$key]["assunto_add"] = utf8_encode($_POST["assunto_add"][$key]);
                        }

                        $assunto_array = json_encode($assunto_contato);
                        $assunto_array = str_replace('\\u', '\\\\u', $assunto_array);

                        $dadosSave = array(
                                    "roteiro"        => $roteiroId, 
                                    "qtde_horas"     => $tipoHoras,
                                    "tipo_de_local"  => $tipoContato,
                                    "codigo"         => $revendaId,
                                    "tipo_de_visita" => $tipoVisita,
                                    "data_visita"    => geraDataBD($dataVisita),
                                    "status"         => $statusRoteiro,
                                    "contato"        => $assunto_array,
                                );

                    } else {

                        $dadosSave = array(
                                    "roteiro"        => $roteiroId, 
                                    "qtde_horas"     => $tipoHoras,
                                    "tipo_de_local"  => $tipoContato,
                                    "codigo"         => $revendaId,
                                    "tipo_de_visita" => $tipoVisita,
                                    "data_visita"    => geraDataBD($dataVisita),
                                    "status"         => $statusRoteiro,
                                );

                    }
                  
                    //verifica se o posto existe na tabela para este roteiro
                    $modelRoteiroPosto = ModelHolder::init("RoteiroPosto");                 
                    $roteiroPosto = $modelRoteiroPosto->find(array("roteiro" => $roteiroId,
                                                                    "tipo_de_local" => $tipoContato,
                                                                    "codigo" => $revendaId),array("roteiro_posto"));
                    $roteiroPostoId = $roteiroPosto[0]["roteiroPosto"];
                    
                    if (empty($roteiroPostoId)) {
                        //insere
                        $modelRoteiroPosto->insert($dadosSave);
                    }else{
                        //update
                        $modelRoteiroPosto->update($dadosSave, $roteiroPostoId);
                    }
                    $auditorLog->retornaDadosSelect($sqlLog)->enviarLog($acao, 'tbl_roteiro', $login_fabrica.'*'.$roteiroId);
                    
                }
            }
            //update list cidade
            if (count($list["cidade"]) > 0) {
                foreach ($list['cidade'] as $valueCidade) {

                    //pegar o idCidade na tabela tbl_cidade passando o cod_ibge                 
                    $modelCidade = ModelHolder::init("Cidade");

                    $cidade = $modelCidade ->find(array("cod_ibge"=>$valueCidade),array("cidade")); 
                    $cidadeId = $cidade[0]["cidade"];
                    

                    if (strlen($cidadeId) == 0) {
                        continue;
                    }
                    
                    //verifica se a cidade  existe na tabela para este roteiro
                    $modelRoteiroCidade = ModelHolder::init("RoteiroPosto");                    
                    $roteiroCidade = $modelRoteiroCidade->find(array( "roteiro"=>$roteiroId,
                                                                      "cidade"=>$cidadeId),array("roteiro_posto"));
                    $roteiroCidadeId = $roteiroCidade[0]["roteiroPosto"];


                    if (empty($roteiroCidadeId)) {

                        //insere
                        $modelRoteiroCidade->insert(array("roteiro" => $roteiroId, "cidade" =>$cidadeId )); 
                    }else{

                        $dadosUP = array("roteiro" => $roteiroId, "cidade" =>$cidadeId);
                        //update
                        $modelRoteiroCidade->update($dadosUP, $roteiroCidadeId);                        
                    }

                }
            }
            if (count($list["tecnicos"]) > 0) {
                
                        $modelTecnico = ModelHolder::init("Tecnico");

                        $tecnico = $modelTecnico->find(array("tecnico" => $tecnico, 
                                                                  "fabrica"=>$login_fabrica), array("tecnico"));
                        $tecnicoId = $tecnico[0]["tecnico"];
                        $modelRoteiroTecnico = ModelHolder::init("RoteiroTecnico");
                        $roteiroTecnico = $modelRoteiroTecnico->find(array( "roteiro"=>$roteiroId,
                                                                              "tecnico"=>$cidadeId),array("roteiro_tecnico"));
                        $roteiroTecnicoId = $roteiroTecnico[0]["roteiroTecnico"];

                        if (empty($roteiroTecnicoId)) {
                            //insere
                            $modelRoteiroTecnico->insert(array("roteiro"=> $roteiroId, "tecnico"=>$tecnicoId));
                        }else{
                            //update
                            $modelRoteiroTecnico->update(array("roteiro"=> $roteiroId, "tecnico"=>$tecnicoId), roteiroTecnicoId);
                        }
            }


            //Fim insere tabelas-

            $msg = (empty($roteiro["roteiro"])) ? "Cadastrado com sucesso" : "Alterado com sucesso";
            echo "<meta http-equiv=refresh content=\"1;URL=".$_SERVER['REQUEST_URI']."\">";
            unset($_POST);
            unset($_REQUEST);
            unset($roteiro);
            unset($list);
            $htmlBuilder = HtmlBuilder::getInstance();
            $htmlBuilder->setValues(array());
        }else{

            $msg_erro = $validation;
    
        }

    }catch(Exception $ex){
        echo $ex->getMessage();exit;
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
$htmlBuilder = HtmlBuilder::getInstance();

if (!empty($roteiro['roteiro'])) {
    $tempUniqueId = $roteiro['roteiro'];
    $anexoNoHash = null;
} else if (strlen($_POST["anexo_chave"]) > 0) {
    $tempUniqueId = $_POST["anexo_chave"];
    $anexoNoHash = true;
} else {
    $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
    $anexoNoHash = true;
}

$layout_menu = "tecnica";
$title = "Cadastro de Roteiros";
include 'cabecalho_new.php';


$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "ajaxform",
    "fancyzoom",
    "select2",
    "dataTable"
    );

include("plugin_loader.php");

?>

<link href="../plugins/jquery_multiselect/css/multi-select.css" media="screen" rel="stylesheet" type="text/css">
<script src="plugins/jquery_multiselect/js/jquery.multi-select.js" type="text/javascript"></script>
<script src="../plugins/quicksearch-master/jquery.quicksearch.js" type="text/javascript"></script>
<script type="text/javascript">

    function getCidades(el){
        $("#cidade").html("");
        var estado = $(el).val() || [];
        var arraicidade = $("#array_cidade").val();
        var array_posto_cidade = $("#array_posto_cidade").val();
        //alert(arraicidade);

        if (estado != "") {
            $.ajax({
                url: '<?php echo $_SERVER['PHP_SELF']?>',
                type: "post",
                data:{
                    estado: estado,
                    action: "getEstados",
                    arraicidade: arraicidade,
                    array_posto_cidade: array_posto_cidade

                },
                complete : function(response){

                    response = response.responseText;
                                    
                    if(response == ""){
                        $(".box-cidade").html("<select id='cidade' name='cidade[]' multiple='multiple' class='span12' ></select>");
                        $('#cidade').multiSelect();
                    }else{
                        $(".box-cidade").html(response);
                        $('#cidade').multiSelect({
                          selectableHeader: "<input type='text' class='search-input span12' autocomplete='off' placeholder='Pesquisar..'>",
                          selectionHeader: "<input type='text' class='search-input span12' autocomplete='off' placeholder='Pesquisar..'>",
                          afterInit: function(ms){
                            var that = this,
                                $selectableSearch = that.$selectableUl.prev(),
                                $selectionSearch = that.$selectionUl.prev(),
                                selectableSearchString = '#'+that.$container.attr('id')+' .ms-elem-selectable:not(.ms-selected)',
                                selectionSearchString = '#'+that.$container.attr('id')+' .ms-elem-selection.ms-selected';

                            that.qs1 = $selectableSearch.quicksearch(selectableSearchString)
                            .on('keydown', function(e){
                              if (e.which === 40){
                                that.$selectableUl.focus();
                                return false;
                              }
                            });

                            that.qs2 = $selectionSearch.quicksearch(selectionSearchString)
                            .on('keydown', function(e){
                              if (e.which == 40){
                                that.$selectionUl.focus();
                                return false;
                              }
                            });
                          },
                          afterSelect: function(){
                            this.qs1.cache();
                            this.qs2.cache();
                          },
                          afterDeselect: function(){
                            this.qs1.cache();
                            this.qs2.cache();
                          }
                        });
                    }
                    
                }
            });
        }else{          
            $(".box-cidade").html("<select id='cidade' name='cidade[]' multiple='multiple' class='span12' ></select>");
            $('#cidade').multiSelect();
        }       
    }


    $(function() {
        $(".select2").select2();
        var datePickerConfig = {maxDate: null, dateFormat: "dd/mm/yy",dayNames: ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'], dayNamesMin: ['D','S','T','Q','Q','S','S','D'], dayNamesShort: ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb','Dom'], monthNames: ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'], monthNamesShort: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'], nextText: 'Próximo', prevText: 'Anterior'};
        
        $(".horas").mask("99:99");
        $("#data_inicio").datepicker(datePickerConfig).mask("99/99/9999");
        $("#data_termino").datepicker(datePickerConfig).mask("99/99/9999");
        $("#data_visita_posto").datepicker(datePickerConfig).mask("99/99/9999");
        $.autocompleteLoad(Array("posto","tecnico", "consumidor", "revenda"));
        

        Shadowbox.init();
        $(".show-log").click(function() {
            var url = 'relatorio_log_alteracao_new.php?' +
                'parametro=tbl_' + $(this).data('object') +
                '&id=' + $(this).data('value');

            if ($(this).data('title'))
                url += "&titulo=" + $(this).data('title');

            Shadowbox.init();

            Shadowbox.open({
                content: url,
                player: "iframe",
                height: 600,
                width: 800
            });
        });
        //Inicio multi-select
        $('#estado').multiSelect({
          selectableHeader: "<input type='text' class='search-input span12' autocomplete='off' placeholder='Pesquisar..'>",
          selectionHeader: "<input type='text' class='search-input span12' autocomplete='off' placeholder='Pesquisar..'>",
          afterInit: function(ms){
            var that = this,
                $selectableSearch = that.$selectableUl.prev(),
                $selectionSearch = that.$selectionUl.prev(),
                selectableSearchString = '#'+that.$container.attr('id')+' .ms-elem-selectable:not(.ms-selected)',
                selectionSearchString = '#'+that.$container.attr('id')+' .ms-elem-selection.ms-selected';

            that.qs1 = $selectableSearch.quicksearch(selectableSearchString)
            .on('keydown', function(e){
              if (e.which === 40){
                that.$selectableUl.focus();
                return false;
              }
            });

            that.qs2 = $selectionSearch.quicksearch(selectionSearchString)
            .on('keydown', function(e){
              if (e.which == 40){
                that.$selectionUl.focus();
                return false;
              }
            });
          },
          afterSelect: function(){
            this.qs1.cache();
            this.qs2.cache();
          },
          afterDeselect: function(){
            this.qs1.cache();
            this.qs2.cache();
          }
        }); 

        $("#assunto").change(function () {
            if ($(this).val() != '') {
                $("#assunto_add").attr('disabled', false);
            } else {
                $("#assunto_add").attr('disabled', true);
            }
        });

        $(window).load(function() {
            /*if ($("#assunto option:selected").val() != '') {
                $("#assunto_add").attr('disabled', false);
            } else {
                $("#assunto_add").attr('disabled', true);
            }*/
        });

        $('#cidade').multiSelect();     
        //fim multi-select
        $('#data_termino').change(function(){
            var dataini = $('#data_inicio').val();
            var datater = $('#data_termino').val();

            if (dataini != '' && datater != '') {
                DAY = 1000 * 60 * 60  * 24;

                var nova1 = dataini.toString().split('/');
                Nova1 = nova1[1]+"/"+nova1[0]+"/"+nova1[2];

                var nova2 = datater.toString().split('/');
                Nova2 = nova2[1]+"/"+nova2[0]+"/"+nova2[2];

                d1 = new Date(Nova1)
                d2 = new Date(Nova2)

                days_passed = Math.round((d2.getTime() - d1.getTime()) / DAY) + 1 ;

                $('#qtde_dias').val(days_passed);
            }else{
                $('#qtde_dias').val('');
            }           
        });

        $('#data_inicio').change(function(){
            var dataini = $('#data_inicio').val();
            var datater = $('#data_termino').val();

            if (dataini != '' && datater != '') {
                DAY = 1000 * 60 * 60  * 24;

                var nova1 = dataini.toString().split('/');
                Nova1 = nova1[1]+"/"+nova1[0]+"/"+nova1[2];

                var nova2 = datater.toString().split('/');
                Nova2 = nova2[1]+"/"+nova2[0]+"/"+nova2[2];

                d1 = new Date(Nova1)
                d2 = new Date(Nova2)

                days_passed = Math.round((d2.getTime() - d1.getTime()) / DAY) + 1 ;

                $('#qtde_dias').val(days_passed);
            }else{
                $('#qtde_dias').val('');
            }           
        });

        $('#add_posto').click(function(){
            var posto        = $('#descricao').val();
            var posto_id     = $("#codigo").val();
            var tipo_contato = $("select[name=tipo_contato] :selected").val();
            var tipo_visita  = $("select[name=tipo_visita] :selected").val();
            var qtde_horas_posto  = $("#qtde_horas_posto").val();
            var data_visita_posto  = $("#data_visita_posto").val();
            /*var assunto      = $("#assunto :selected").val();
            var assunto_html = '';
            if ($("#assunto :selected").html() != "Selecione") {
                assunto_html = $("#assunto :selected").html();
            }
            var assunto_add  = $("#assunto_add").val();*/
            var xtipo_visita = "";
            var xtipo_contato = "";
            if (posto == '' && posto_id == '') {
                alert("Favor preencha os campos Código e Nome");
                $('#descricao').focus();
                return false;
            } else if (tipo_contato == '') {
                alert("Favor escolha um Tipo de contato");
                $('select[name=tipo_contato]').focus();
                return false;
            } else if (tipo_visita == '') {
                alert("Favor escolha um Tipo de visita");
                $('select[name=tipo_visita]').focus();
                return false;
            } else if (qtde_horas_posto == '') {
                alert("Favor preencha a Qtde Horas");
                $('#qtde_horas_posto').focus();
                return false;
            } else if (data_visita_posto == '') {
                alert("Favor preencha a Data Visita");
                $('#data_visita_posto').focus();
                return false;
            } else {

                if (tipo_visita == 'VA') {
                    xtipo_visita = "Visita Admistrativa";
                } else if (tipo_visita == 'VC') {
                    xtipo_visita = "Visita Comercial";
                } else if (tipo_visita == 'VT') {
                    xtipo_visita = "Visita Técnica";
                } else if (tipo_visita == 'CM') {
                    xtipo_visita = "Clínica Makita";
                } else if (tipo_visita == 'FE') {
                    xtipo_visita = "Feira/Evento";
                } else if (tipo_visita == 'TN') {
                    xtipo_visita = "Treinamento";
                }


                if (tipo_contato == 'CL') {
                    xtipo_contato = "Cliente";
                } else if (tipo_contato == 'RV') {
                    xtipo_contato = "Revenda";
                } else if (tipo_contato == 'PA') {
                    xtipo_contato = "Posto Autorizado";
                }
                
                 var conteudo_posto = '<tr class="tr-'+posto_id+'">'+
                                    '<td class="tac">'+
                                        '<input type="hidden" name="posto[]" value="' + posto_id + '" />'+
                                        '<input type="hidden" name="data_visita_postos[]" value="' + data_visita_posto + '" />'+    
                                        '<input type="hidden" name="qtde_horas_postos[]" value="' + qtde_horas_posto + '" />'+  
                                        '<input type="hidden" name="tipo_visita_posto[]" value="' + tipo_visita + '" />'+
                                        '<input type="hidden" name="tipo_contato_posto[]" value="' + tipo_contato + '" />'+
                                        xtipo_contato+
                                    '</td>'+
                                    '<td class="tac">'+posto_id+'</td>'+
                                    '<td>'+posto+'</td>'+
                                    '<td class="tac">'+qtde_horas_posto+'</td>'+
                                    '<td class="tac">'+data_visita_posto+'</td>'+
                                    '<td class="tac">'+xtipo_visita+'</td>'+
                                    '<td class="tac"><button type="button" data-id="'+posto_id+'" class="btn rm_posto btn-mini btn-danger"><i class="icon-remove icon-white"></i></button></td></tr>';


                $('#resultado-postos').append(conteudo_posto);
                $("#descricao").val('');
                $("#codigo").val('');
                $("#tipo_contato").val('');
                $("#tipo_visita").val('');
                $("#qtde_horas_posto").val('');

           
            }

        });
            $("select[name=postos] option:selected").each(function () {
                var hidden = $(this).attr("class");
                 
                $(this).remove();
                $('input[value="'+ hidden +'"]').remove();
            });
        $(document).on("click", ".rm_posto", function(){
            var id = $(this).data("id");
            var excluir_bd = $(this).data("exbd");
            

            if (excluir_bd == true) {
                $.ajax({
                    url: '<?php echo $_SERVER['REQUEST_URI']?>',
                    type: "POST",
                    dataType: "JSON",
                    data:{
                        ajax_remove_agenda_visita: true,
                        id: id
                    },
                    success : function(dados){
                        if (dados.success == '1') {
                            alert("Removido com sucesso!");
                            $('.tr-'+id).remove();
                        } else {
                            alert("Erro ao remover!");
                        }
                        
                    }
                });
            } else {
                $('.tr-'+id).remove();
            }
        });

        $('#add_tecnico').click(function(){
            var tecnico = $('#nome_tecnico').val();
            var tecnico_id = $("#cpf_tecnico").val();

            if (tecnico_id && tecnico) {
                var option = '<option value="' + tecnico + '" class="' + tecnico_id + '">'+ tecnico_id+' - '+ tecnico + '</option>';
                var hidden = '<input type="hidden" name="tecnico[]" id="' + tecnico_id + '" value="' + tecnico_id + '" />';
                $('#tecnicos').append(option);
                $('#tecnicos').append(hidden);
                $("#nome_tecnico").val('');
                $("#cpf_tecnico").val('');
            }else{
                alert("Favor preencha os campos CPF e Técnico Nome");
            }
        });

        $('#add_consumidor').click(function(){
            var consumidor = $('#nome_consumidor').val();
            var cpf_consumidor = $("#cpf_consumidor").val();
            var qtde_horas_consumidor = $("#qtde_horas_consumidor").val();
            var visita_cliente = $("#visita_cliente").val();

            if (cpf_consumidor && consumidor) {
                var option  = '<option value="' + consumidor + '" class="' + cpf_consumidor + '">'+ cpf_consumidor+' - '+ consumidor + ' - Qtde horas: '+ qtde_horas_consumidor + ' - Tipo Visita: '+ visita_cliente + '</option>';
                var hidden  = '<input type="hidden" name="consumidor[]" id="' + cpf_consumidor + '" value="' + cpf_consumidor + '" />';
                var hidden2 = '<input type="hidden" name="qtde_horas_consumidores[]" id="' + qtde_horas_consumidor + '" value="' + qtde_horas_consumidor + '" />';
                var hidden3 = '<input type="hidden" name="tipo_visita_consumidor[]" id="' + visita_cliente + '" value="' + visita_cliente + '" />';
                $('#consumidores').append(option);
                $('#consumidores').append(hidden);
                $('#consumidores').append(hidden2);
                $('#consumidores').append(hidden3);
                $("#nome_consumidor").val('');
                $("#cpf_consumidor").val('');
                $("#qtde_horas_consumidor").val('');
            }else{
                alert("Favor preencha os campos CPF e Nome do Cliente");
            }
        });

        $('#rm_consumidor').click(function(){
            $("select[name=consumidores] option:selected").each(function () {
                var hidden = $(this).attr("class");
                 
                $(this).remove();
                $('input[value="'+ hidden +'"]').remove();
            });
        });

        $('#rm_tecnico').click(function(){
            $("select[name=tecnicos] option:selected").each(function () {
                var hidden = $(this).attr("class");
                 
                $(this).remove();
                $('input[value="'+ hidden +'"]').remove();
            });
        });

        $("span[rel=lupa]").click(function () {
            var estado = $("#estado").val();
            var cidade = $("#cidade").val();

            $("input[name=lupa_config]").attr({"estado": estado, "cidade":cidade})
            $.lupa($(this), ["estado", "cidade", "refe"]);
        });

        $("span[rel=lupa_tecnico]").click(function () {
            var estado = $("#estado").val();
            var cidade = $("#cidade").val();

            $("input[name=lupa_config]").attr({"estado": estado, "cidade":cidade})
            $.lupa($(this), ["estado", "cidade" , "tipotecnico" , "refe"]);
        });

        setTimeout(getCidades($("#estado")),500);

        $("span[rel=lupa_consumidor]").click(function () {
            var estado = $("#estado").val();
            var cidade = $("#cidade").val();

            $("input[name=lupa_config]").attr({"estado": estado, "cidade":cidade})
            $.lupa($(this), ["estado", "cidade" , "tipotecnico", "refe"]);
        });

        $("select[name=tipo_contato]").change(function () {
            var tipo_contato = $(this).val();
            var label_codigo = "";
            var label_descricao = "";
            $("#codigo").removeAttr("disabled");
            $("#descricao").removeAttr("disabled");

            if (tipo_contato == 'CL') {
                label_codigo = "CPF/CNPJ Cliente";
                label_descricao = "Nome do Cliente";
                $("input[name=lupa_config]").attr("tipo", "consumidor");
                $(".lup_cod").attr("parametro", "cnpj");
                $(".lup_desc").attr("parametro", "nome_consumidor");
            } else if (tipo_contato == 'RV') {
                label_codigo = "CNPJ Revenda";
                label_descricao = "Nome da Revenda";
                $("input[name=lupa_config]").attr("tipo", "revenda");
                $(".lup_cod").attr("parametro", "cnpj");
                $(".lup_desc").attr("parametro", "razao_social");
            } else if (tipo_contato == 'PA') {
                label_codigo = "CNPJ do Posto";
                label_descricao = "Nome do Posto";
                $("input[name=lupa_config]").attr("tipo", "posto");
                $(".lup_cod").attr("parametro", "codigo");
                $(".lup_desc").attr("parametro", "nome");
            } else {
                label_codigo = "Código";
                label_descricao = "Nome";
                $("#codigo").attr("disabled", true);
                $("#descricao").attr("disabled", true);
            }

            $(".label_codigo").html(label_codigo);
            $(".label_descricao").html(label_descricao);

        });

        $("#abre_consumidor").click(function() {
            window.open('consumidor_cadastro.php');
        });

        $("#abre_revenda").click(function() {
            window.open('revenda_cadastro.php');
        });

    });
    function retorna_tecnico(retorno){      
        $("#cpf_tecnico").val(retorno.cpf);
        $("#nome_tecnico").val(retorno.nome);
    }

    function retorna_consumidor(retorno){       
        $("#descricao").val(retorno.nome);
        $("#codigo").val(retorno.cpf);
    }

    function retorna_posto(retorno){
        $("#codigo").val(retorno.cnpj);
        $("#descricao").val(retorno.nome);
    }

    function retorna_revenda(retorno){
        $("#codigo").val(retorno.cnpj);
        $("#descricao").val(retorno.razao);
    }
</script>
<style>
    .AutoListModel {
        display: none;
    }

    .ms-container{
      background: transparent no-repeat 50% 50%;
      width: 300px !important;
    }

</style>

<div class="row tac">
    <div class="span2"></div>
    <div class="span3">
        <button type="button" id="abre_consumidor" class="btn btn-info">Cadastro Consumidor</button>
    </div>
    <div class="span2"></div>
    <div class="span3">
        <button type="button" id="abre_revenda" class="btn btn-info">Cadastro Revenda</button>
    </div>
    <div class="span2"></div>
</div>
<br>

<?php if (count($msg_erro["msg"]) > 0) {?>
    <div class="alert alert-error">
        <h4><?php echo implode("<br/>", $msg_erro["msg"]);?></h4>
        <h4><?php echo implode("<br/>", $msg_erro["msg"]["obg"]);?></h4>
    </div>
<?php }?>
<?php if (strlen($msg) > 0 AND count($msg_erro["msg"])==0) { ?>
    <div class="alert alert-success">
        <h4><? echo $msg; ?></h4>
    </div>
<?php }?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_roteiro_tecnico' METHOD='POST' ACTION='<?php echo $_SERVER['REQUEST_URI']?>' align='center' class='form-search form-inline tc_formulario' >
    <input type="hidden" name="roteiro" value="<?php echo $roteiro['roteiro'] ?>"/>
    <div class='titulo_tabela '>Cadastro de Roteiro</div>
    <br/>

    <div class='row-fluid'>
        <div class='span1'></div>
        <div class='span3'>
            <div class='control-group <?php echo (in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicial'>Data Início</label>
                <div class='controls controls-row'>
                    <div class='span8'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="data_inicio" id="data_inicio" size="12" maxlength="10" class='span12' value= "<?php echo $roteiro['data_inicio']?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span3'>
            <div class='control-group <?php echo (in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Término</label>
                <div class='controls controls-row'>
                    <div class='span8'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="data_termino" id="data_termino" size="12" maxlength="10" class='span12' value="<?php echo $roteiro['data_termino']?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?php echo (in_array("tipo_roteiro", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='tipo_roteiro'>Tipo de Roteiro</label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <h5 class='asteristico'>*</h5>
                        <select id="tipo_roteiro" name="tipo_roteiro" class='span12' >
                            <option value="">Selecione o tipo do Roteiro</option>
                            <?php 
                                $tiporoteiro = array('RA' => 'Roteiro Administrativo' , 'RT' => 'Roteiro Técnico' ); 
                                foreach ($tiporoteiro as $key => $value) {
                                    $selected = ($key==$roteiro["tipo_roteiro"]) ? "SELECTED" : "";
                            ?>
                                    <option <?php echo $selected; ?> value="<?php echo $key; ?>">
                                        <?php echo $value; ?>
                                    </option>
                            <?php }?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span1'></div>
    </div>
    <div class="container">
        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span5'>
                <div class='control-group <?php echo (in_array("estado", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='estado'>Estado</label>
                    <div class='controls controls-row'>
                        <div class='span2 input-append'>
                        <h5 class='asteristico'>*</h5>
                            <select id="estado" name="estado[]" class='span2' multiple='multiple' onchange="getCidades(this);" >                                
                                <?php                               
                                    $estados = getEstados1();
                                    $ar_estado = $_POST['estado'];                              
                                    foreach($estados as $item){

                                        if (in_array($item['estado'], $ar_estado)) {
                                            $selected = "SELECTED";                                             
                                        }else{
                                        
                                            $selected = ""; 
                                        }
                                        
                                        foreach ($roteiro["posto"] as $key => $valorkey) {

                                            $keyRoteiroPosto = $valorkey['roteiro_posto'];

                                            $sqlEstado = "SELECT cidade FROM tbl_roteiro_posto WHERE roteiro_posto = {$keyRoteiroPosto};";
                                            $resEstado = pg_query($con,$sqlEstado);
                                            $value = pg_fetch_result($resEstado, 0, cidade);

                                            if (count($value) > 0) {
                                                
                                                $sqlUF = "SELECT estado FROM tbl_cidade WHERE cidade = {$value};";
                                                $resUF = pg_query($con,$sqlUF);

                                                $UFCidade = pg_fetch_result($resUF, 0, estado);

                                                if ($item["estado"] == $UFCidade) {
                                                
                                                    $selected = "SELECTED";                                             
                                                }else{
                                                
                                                    $selected = ""; 
                                                }
                                            }
                                        }
                                        if (array_key_exists('posto', $roteiro)) {
                                            $selected = "";
                                            foreach ($roteiro['posto'] as $key => $value) {
                                                $id_roteiro_posto = $roteiro['posto'][$key]['roteiro_posto'];
                                                $sqlRoteiroPosto = "SELECT posto,cidade FROM tbl_roteiro_posto WHERE roteiro_posto = {$id_roteiro_posto};";
                                                $resRoteiroPosto = pg_query($con,$sqlRoteiroPosto);
                                                if (pg_num_rows($resRoteiroPosto) > 0) {
                                                    $postoRoteiro = pg_fetch_result($resRoteiroPosto, 0, posto);
                                                    $cidadeRoteiro = pg_fetch_result($resRoteiroPosto, 0, cidade);
                                                    if (!empty($postoRoteiro) ) {
                                                        //pesquiso pelo estado do posto
                                                        $sqlPostoFabrica = "SELECT contato_estado FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$postoRoteiro}";
                                                        $resPostoFabrica = pg_query($con,$sqlPostoFabrica);

                                                        $estadoPosto = pg_fetch_result($resPostoFabrica, 0, contato_estado);

                                                        if ($item["estado"] == $estadoPosto) {
                                                
                                                            $selected = "SELECTED";
                                                        }
                                                    }
                                                    if (!empty($cidadeRoteiro)) {
                                                        //pesquiso pelo estado da cidade
                                                        $sqlCidade = "SELECT estado FROM tbl_cidade WHERE cidade = {$cidadeRoteiro}";
                                                        $resCidade = pg_query($con,$sqlCidade);

                                                        $estadoCidade = pg_fetch_result($resCidade, 0, estado);
                                                        
                                                        if ($item["estado"] == $estadoCidade) {
                                                
                                                            $selected = "SELECTED";                                             
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                ?>
                                    <option value="<?php echo $item['estado']; ?>" <?php echo $selected; ?>>
                                        <?php echo $item["nome"]; ?>
                                    </option>

                                <?php }?>

                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span5'>
                    <div class='control-group'>
                        <label class='control-label' for='cidade'>Cidade</label>
                        <input type='hidden' id='array_cidade' value='<?php echo json_encode($_POST["cidade"]); ?>'>
                        <input type='hidden' id='array_posto_cidade' value='<?php echo json_encode($roteiro["posto"]); ?>'>
                        <div class='controls controls-row'>
                            <div class='span12 input-append box-cidade'>                                
                                <select id="cidade" name="cidade" class='span12' >
                                <?php
                                    foreach ($roteiro["posto"] as $key => $valorkey) {
                                        
                                        $keyRoteiroPosto = $valorkey['roteiro_posto'];

                                        $sqlEstado = "SELECT cidade FROM tbl_roteiro_posto WHERE roteiro_posto = {$keyRoteiroPosto};";
                                        $resEstado = pg_query($con,$sqlEstado);
                                        $value = pg_fetch_result($resEstado, 0, cidade);

                                        if (count($value) > 0) {
                                            
                                            $sqlUF = "SELECT cidade,nome,estado FROM tbl_cidade WHERE cidade = {$value};";
                                            $resUF = pg_query($con,$sqlUF);

                                            $UFCidade = pg_fetch_result($resUF, 0, estado);
                                            $NomeCidade = pg_fetch_result($resUF, 0, nome);
                                            $KeyCidade = pg_fetch_result($resUF, 0, cidade);

                                            ?>
                                            <option value="<?php echo $KeyCidade; ?> SELECTED" >
                                                <?php echo $UFCidade." ".$NomeCidade; ?>
                                            </option>
                                            <?php
                                        }
                                    }
                                ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span1'></div>
            </div>
        </div>      
        <br>
        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span2'>
                <div class='control-group <?php echo (in_array("status_roteiro", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='tipo_roteiro'>Status Roteiro</label>
                    <div class='controls controls-row'>
                        <h5 class='asteristico'>*</h5>
                        <select name="status_roteiro" id="status_roteiro" class='span12' >
                            <option value="">Selecione ...</option>
                            <option value="AC" <?php echo ($statusRoteiro == "AC") ? "selected" : "";?>>A Confirmar</option>
                            <option value="CF" <?php echo ($statusRoteiro == "CF") ? "selected" : "";?>>Confirmado</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="span5">
                <div class="control-gru">
                    <div class='control-group <?php echo (in_array("solicitante", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='solicitante'>Solicitante</label>
                        <div class='controls controls-row'>
                            <div class='span12 input-append'>
                                <h5 class='asteristico'>*</h5>
                                <input type="text" id="solicitante" name="solicitante" maxlength="20" class='span12' value="<?php echo $roteiro['solicitante'] ?>" >
                            </div>
                        </div>
                    </div>                  
                </div>              
            </div>
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label' for='ativo'>Ativo</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <select name="ativo" id="ativo" class='span12' >
                                <option value=""></option>
                                <option value="t" <?php echo $selected = ($roteiro["ativo"] == 't') ? "SELECTED" : ""; ?> >SIM</option>
                                <option value="f" <?php echo $selected = ($roteiro["ativo"] == 'f') ? "SELECTED" : ""; ?> >NÃO</option>                             
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span1'></div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class='span2'>
                <div class='control-group <?php echo (in_array("tipo_contato", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='tipo_contato'>Tipo de Contato</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <?php
                                if (strlen($tipo_contato) > 0) {

                                    if ($tipo_contato == "CL") {
                                        $label_cod  = "CPF/CNPJ Cliente";
                                        $label_desc = "Nome do Cliente";
                                    } elseif ($tipo_contato == "RV") {
                                        $label_cod  = "CNPJ Revenda";
                                        $label_desc = "Nome da Revenda";
                                    } elseif ($tipo_contato == "PA") {
                                        $label_cod  = "CNPJ Posto";
                                        $label_desc = "Nome do Posto";
                                    } else {
                                        $label_cod  = "Código";
                                        $label_desc = "Nome";
                                    }
                                }
                            ?>
                            <select name="tipo_contato" id="tipo_contato"  class='span12' >
                                <option value="">Selecione ...</option>
                                <option value="CL" <?php echo ($tipo_contato == "CL") ? "selected" : "";?>>Cliente</option>
                                <option value="RV" <?php echo ($tipo_contato == "RV") ? "selected" : "";?>>Revenda</option>
                                <option value="PA" <?php echo ($tipo_contato == "PA") ? "selected" : "";?>>Posto Autorizado</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label label_codigo' for='codigo'><?php echo $label_cod;?> </label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" name="codigo" disabled id="codigo" class='span12' value="<?php echo $codigo ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" class="lup_cod" name="lupa_config" tipo="posto" parametro="codigo" cidade="" refe="roteiro" estado=""/>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label label_descricao' for='descricao'><?php echo $label_desc;?> </label>
                    <div class='controls controls-row'>
                        <div class='span11 input-append'>
                            <input type="text" name="descricao" disabled id="descricao" class='span12' value="<?php echo $descricao ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" class="lup_desc" name="lupa_config" tipo="posto" parametro="nome" cidade="" refe="roteiro" estado=""/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span2'>
                <div class='control-group <?php echo (in_array("qtde_horas_posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='qtde_horas_posto'>Qtde Horas</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" name="qtde_horas_posto" id="qtde_horas_posto" size="12" maxlength="10" class='span12 horas' value= "<?php echo $qtde_horas_posto;?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?php echo (in_array("tipo_visita", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='tipo_visita'>Tipo de Visita</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <select name="tipo_visita" id="tipo_visita" class='span12'>
                                <option value="">Selecione ...</option>
                                <option value="VA" <?php echo ($tipo_visita == "VA") ? "selected" : "";?>>Visita Admistrativa</option>
                                <option value="VC" <?php echo ($tipo_visita == "VC") ? "selected" : "";?>>Visita Comercial</option>
                                <option value="VT" <?php echo ($tipo_visita == "VT") ? "selected" : "";?>>Visita Técnica</option>
                                <option value="CM" <?php echo ($tipo_visita == "CM") ? "selected" : "";?>>Clínica Makita</option>
                                <option value="FE" <?php echo ($tipo_visita == "FE") ? "selected" : "";?>>Feira/Evento</option>
                                <option value="TN" <?php echo ($tipo_visita == "TN") ? "selected" : "";?>>Treinamento</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?php echo (in_array("data_visita_posto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_visita_posto'>Data Visita</label>
                    <h5 class='asteristico'>*</h5>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" name="data_visita_posto" id="data_visita_posto" class='span12' value= "<?php echo $data_visita_posto;?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label'></label>           
                    <div class='controls controls-row'>
                        <div class="span12">
                            <input type="button" id="add_posto" class='btn' value="Adicionar" />    
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span4'>
                <label class='control-label' for='assunto'>Assunto</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <select class="span12" id="assunto" name="assunto" >
                            <option value="" >Selecione</option>
                            <?php
                           /* $sqlAssunto = " SELECT roteiro_assunto, assunto FROM tbl_roteiro_assunto WHERE ativo AND fabrica = $login_fabrica ORDER BY assunto ASC";
                            $resAssunto = pg_query($con, $sqlAssunto);

                            while ($row = pg_fetch_object($resAssunto)) {
                                $selected = ($row->roteiro_assunto == $_POST["roteiro_assunto"]) ? "selected" : "";
                                echo "<option value='{$row->roteiro_assunto}' {$selected} >".utf8_decode($row->assunto)."</option>";
                            }*/
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <label class='control-label' for='data_visita_posto'>Informações Assunto</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <textarea name="assunto_add" class="input" id="assunto_add" value="<?=$assunto_add?>"></textarea>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label'></label>           
                    <div class='controls controls-row'>
                        <div class="span12">
                            <input type="button" id="add_posto" class='btn' value="Adicionar" />    
                        </div>
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div> -->
        <br />
        <div class='container'>
            <div class='row-fluid'>
                <div class='span1'></div>
                <div class='span10'>
                    <table class="table table-striped table-bordered table-hover table-fixed">
                        <thead>
                            <tr class="titulo_coluna">
                                <th>Tipo Contato</th>
                                <th>Código</th>
                                <!-- <th>Assunto</th>
                                <th>Assunto Inf.</th> -->
                                <th class="tal">Nome</th>
                                <th>Qtde Horas</th>
                                <th>Data Visita</th>
                                <th>Tipo Visita</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody id="resultado-postos">
                        <?php
                            $ar_posto   = array();
                            $ar_cliente = array();
                            $ar_revenda = array();

                            if (strlen($roteiro['roteiro']) > 0 && $edit) {

                                $ar_posto   = getRoteioPosto($roteiro["roteiro"]);
                                $ar_cliente = getRoteioCliente($roteiro["roteiro"]);
                                $ar_revenda = getRoteioRevenda($roteiro["roteiro"]);

                                if (!empty($ar_posto)) {

                                    foreach ($ar_posto as $key => $rows) {
                                        $xtipo_de_visita  = getLegendaTipoVisita($rows["tipo_de_visita"]);
                                        $xtipo_de_contato = getLegendaTipoContato($rows["tipo_de_local"]);

                                        $assunto      = ""; 
                                        $assunto_html = "";
                                        $assunto_add  = "";

                                        if (!empty($rows["contato"])) {
                                            
                                            $assunto_array = getAssuntoCompleto($rows["contato"]);
                                            
                                            $assunto      = $assunto_array[0]["assunto"];
                                            $assunto_html = $assunto_array[0]["assunto_html"];
                                            $assunto_add  = $assunto_array[0]["assunto_add"];
                                        }

                                        $conteudo_posto .= '<tr class="tr-'.$rows["roteiro_posto"].'">
                                                            <td class="tac">
                                                                '.$xtipo_de_contato[$rows["tipo_de_local"]].'
                                                            </td>
                                                            <td class="tac">'.$rows["codigo"].'</td>
                                                            <td>'.$rows["nome"].'</td>
                                                            <td class="tac">'.$rows["qtde_horas"].'</td>
                                                            <td class="tac">'.geraDataNormal($rows["data_visita"]).'</td>
                                                            <td class="tac">'.$xtipo_de_visita.'</td>
                                                            <td class="tac"><button type="button" data-exbd="true" data-id="'.$rows["roteiro_posto"].'" class="btn rm_posto btn-mini btn-danger"><i class="icon-remove icon-white"></i></button></td></tr>';


                                    }

                                }

                                if (!empty($ar_cliente)) {

                                    foreach ($ar_cliente as $key => $rows) {
                                        $xtipo_de_visita  = getLegendaTipoVisita($rows["tipo_de_visita"]);
                                        $xtipo_de_contato = getLegendaTipoContato($rows["tipo_de_local"]);

                                        $assunto      = ""; 
                                        $assunto_html = "";
                                        $assunto_add  = "";

                                        if (!empty($rows["contato"])) {
                                            
                                            $assunto_array = getAssuntoCompleto($rows["contato"]);
                                            
                                            $assunto      = $assunto_array[0]["assunto"];
                                            $assunto_html = $assunto_array[0]["assunto_html"];
                                            $assunto_add  = $assunto_array[0]["assunto_add"];
                                        }

                                        $conteudo_posto .= '<tr class="tr-'.$rows["roteiro_posto"].'">
                                                            <td class="tac">
                                                                '.$xtipo_de_contato[$rows["tipo_de_local"]].'
                                                            </td>
                                                            <td class="tac">'.$rows["codigo"].'</td>
                                                            <td>'.$rows["nome"].'</td>
                                                            <td class="tac">'.$rows["qtde_horas"].'</td>
                                                            <td class="tac">'.geraDataNormal($rows["data_visita"]).'</td>
                                                            <td class="tac">'.$xtipo_de_visita.'</td>
                                                            <td class="tac"><button type="button" data-exbd="true" data-id="'.$rows["roteiro_posto"].'" class="btn rm_posto btn-mini btn-danger"><i class="icon-remove icon-white"></i></button></td></tr>';


                                    }
                                    
                                }
                                if (!empty($ar_revenda)) {

                                    foreach ($ar_revenda as $key => $rows) {
                                        $xtipo_de_visita  = getLegendaTipoVisita($rows["tipo_de_visita"]);
                                        $xtipo_de_contato = getLegendaTipoContato($rows["tipo_de_local"]);

                                        $assunto      = ""; 
                                        $assunto_html = "";
                                        $assunto_add  = "";

                                        if (!empty($rows["contato"])) {
                                            
                                            $assunto_array = getAssuntoCompleto($rows["contato"]);
                                            
                                            $assunto      = $assunto_array[0]["assunto"];
                                            $assunto_html = $assunto_array[0]["assunto_html"];
                                            $assunto_add  = $assunto_array[0]["assunto_add"];
                                        }

                                        $conteudo_posto .= '<tr class="tr-'.$rows["roteiro_posto"].'">
                                                            <td class="tac">
                                                                '.$xtipo_de_contato[$rows["tipo_de_local"]].'
                                                            </td>
                                                            <td class="tac">'.$rows["codigo"].'</td>
                                                            <td>'.$rows["nome"].'</td>
                                                            <td class="tac">'.$rows["qtde_horas"].'</td>
                                                            <td class="tac">'.geraDataNormal($rows["data_visita"]).'</td>
                                                            <td class="tac">'.$xtipo_de_visita.'</td>
                                                            <td class="tac"><button type="button" data-exbd="true" data-id="'.$rows["roteiro_posto"].'" class="btn rm_posto btn-mini btn-danger"><i class="icon-remove icon-white"></i></button></td></tr>';


                                    }

                                }
                                echo $conteudo_posto;
                            }

                            if (count($_POST["posto"]) > 0 && strlen($roteiro['roteiro']) == 0)  {

                                for ($i=0; $i < count($_POST["posto"]); $i++) { 
                                    if ($_POST["tipo_contato_posto"][$i] == "PA") {

                                        $ar_posto   = getPosto($_POST["posto"][$i]);
                                        if (!empty($ar_posto)) {
                                                $xtipo_de_visita  = getLegendaTipoVisita($_POST["tipo_visita_posto"][$i]);
                                                $xtipo_de_contato = getLegendaTipoContato($_POST["tipo_contato_posto"][$i]);
                                                $conteudo_posto .= '<tr class="tr-'.$i.'">
                                                                    <td class="tac">
                                                                        <input type="hidden" name="posto[]" value="'.$_POST["posto"][$i].'" />
                                                                        <input type="hidden" name="data_visita_postos[]" value="' . $_POST["data_visita_postos"][$i] . '" />    
                                                                        <input type="hidden" name="qtde_horas_postos[]" value="' . $_POST["qtde_horas_postos"][$i] . '" />  
                                                                        <input type="hidden" name="tipo_visita_posto[]" value="' . $_POST["tipo_visita_posto"][$i] . '" />
                                                                        <input type="hidden" name="tipo_contato_posto[]" value="' . $_POST["tipo_contato_posto"][$i] . '" />
                                                                        '.$xtipo_de_contato[$_POST["tipo_contato_posto"][$i]].'
                                                                    </td>
                                                                    <td class="tac">'.$ar_posto["codigo"].'</td>
                                                                    <td>'.$ar_posto["nome"].'</td>
                                                                    <td class="tac">'.$_POST["qtde_horas_postos"][$i].'</td>
                                                                    <td class="tac">'.$_POST["data_visita_postos"][$i].'</td>
                                                                    <td class="tac">'.$xtipo_de_visita.'</td>
                                                                    <td class="tac"><button type="button" data-id="'.$i.'" class="btn rm_posto btn-mini btn-danger"><i class="icon-remove icon-white"></i></button></td></tr>';



                                        }

                                    }

                                    if ($_POST["tipo_contato_posto"][$i] == "CL") {
                                        $ar_cliente = getCliente($_POST["posto"][$i]);
                                        if (!empty($ar_cliente)) {

                                                $xtipo_de_visita  = getLegendaTipoVisita($_POST["tipo_visita_posto"][$i]);
                                                $xtipo_de_contato = getLegendaTipoContato($_POST["tipo_contato_posto"][$i]);
                                                $conteudo_posto .= '<tr class="tr-'.$i.'">
                                                                    <td class="tac">
                                                                        <input type="hidden" name="posto[]" value="'.$_POST["posto"][$i].'" />
                                                                        <input type="hidden" name="data_visita_postos[]" value="' . $_POST["data_visita_postos"][$i] . '" />    
                                                                        <input type="hidden" name="qtde_horas_postos[]" value="' . $_POST["qtde_horas_postos"][$i] . '" />  
                                                                        <input type="hidden" name="tipo_visita_posto[]" value="' . $_POST["tipo_visita_posto"][$i] . '" />
                                                                        <input type="hidden" name="tipo_contato_posto[]" value="' . $_POST["tipo_contato_posto"][$i] . '" />
                                                                        '.$xtipo_de_contato[$_POST["tipo_contato_posto"][$i]].'
                                                                    </td>
                                                                    <td class="tac">'.$ar_cliente["codigo"].'</td>
                                                                    <td>'.$ar_cliente["nome"].'</td>
                                                                    <td class="tac">'.$_POST["data_visita_postos"][$i].'</td>
                                                                    <td class="tac">'.$_POST["qtde_horas_postos"][$i].'</td>
                                                                    <td class="tac">'.$xtipo_de_visita.'</td>
                                                                    <td class="tac"><button type="button" data-id="'.$i.'" class="btn rm_posto btn-mini btn-danger"><i class="icon-remove icon-white"></i></button></td></tr>';



                                        }

                                    }

                                    if ($_POST["tipo_contato_posto"][$i] == "RV") {
                                            $ar_revenda = getRevenda($_POST["posto"][$i]);
                                    
                                            if (!empty($ar_revenda)) {

                                                $xtipo_de_visita  = getLegendaTipoVisita($_POST["tipo_visita_posto"][$i]);
                                                $xtipo_de_contato = getLegendaTipoContato($_POST["tipo_contato_posto"][$i]);
                                                $conteudo_posto .= '<tr class="tr-'.$i.'">
                                                                    <td class="tac">
                                                                        <input type="hidden" name="posto[]" value="'.$_POST["posto"][$i].'" />
                                                                        <input type="hidden" name="data_visita_postos[]" value="' . $_POST["data_visita_postos"][$i] . '" />    
                                                                        <input type="hidden" name="qtde_horas_postos[]" value="' . $_POST["qtde_horas_postos"][$i] . '" />  
                                                                        <input type="hidden" name="tipo_visita_posto[]" value="' . $_POST["tipo_visita_posto"][$i] . '" />
                                                                        <input type="hidden" name="tipo_contato_posto[]" value="' . $_POST["tipo_contato_posto"][$i] . '" />
                                                                        '.$xtipo_de_contato[$_POST["tipo_contato_posto"][$i]].'
                                                                    </td>
                                                                    <td class="tac">'.$ar_revenda["codigo"].'</td>
                                                                    <td>'.$ar_revenda["nome"].'</td>
                                                                    <td class="tac">'.$_POST["data_visita_postos"][$i].'</td>
                                                                    <td class="tac">'.$_POST["qtde_horas_postos"][$i].'</td>
                                                                    <td class="tac">'.$xtipo_de_visita.'</td>
                                                                    <td class="tac"><button type="button" data-id="'.$i.'" class="btn rm_posto btn-mini btn-danger"><i class="icon-remove icon-white"></i></button></td></tr>';





                                            }
                                    }

                                }
                                                         
                                echo $conteudo_posto;
                            }
                        ?>        
                        </tbody>
                    </table><hr>
                </div>
                <div class='span1'></div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class='span6'>
                <div class='control-group'>
                    <label class='control-label' for='admin'>Responsável pelo Roteiro</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select name="tecnico" id="tecnico" class="span12 select2">
                                <option value="">Selecione ...</option>
                                <?php foreach (getTecnicos() as $key => $rows) {?>
                                    <option <?php echo ($roteiro["tecnicoid"] == $rows["tecnico"]) ? "selected" : "";?> value="<?php echo $rows["tecnico"];?>"><?php echo $rows["nome"];?> </option>
                                <?php }?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div>
        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span3'>
                <div class='control-group <?php echo (in_array("qtde_dias", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicial'>Qtde Dias</label>
                    <div class='controls controls-row'>
                        <div class='span5'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="qtde_dias" id="qtde_dias" size="12" maxlength="10" class='span12'  readonly="true" value= "<?php echo $roteiro['qtde_dias']?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span7">
                <div class='control-group '>
                    <label class='control-label' for='descricao_posto'>Comentários Internos</label>
                    <div class='controls controls-row'>
                        <textarea name="excecoes" class="span11" > <?php echo $roteiro["excecoes"] ?> </textarea> 
                    </div>
                </div>
            </div>
            <div class='span1'></div>
        </div>
        <br/>
        <div class='row-fluid'>
            <div class='span12'>
                <?php
                     $boxUploader = array(
                        "div_id" => "div_anexos",
                        "prepend" => $anexo_prepend,
                        "context" => "roteiro",
                        "unique_id" => $tempUniqueId,
                        "hash_temp" => $anexoNoHash,
                        "bootstrap" => true
                    );
                    include "../box_uploader.php";
                ?>
            </div>
        </div>
        <p><br/>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'), 'save');">Salvar</button>
            <button class='btn btn-warning' id="btn_acao" type="button"  onclick="window.location = '<?php echo $_SERVER['PHP_SELF'];?>'">Limpar</button>
                    <input type="hidden" name="tempUniqueId" value="<?php echo $tempUniqueId;?>">
                    <input type="hidden" name="temHash" value="<?php echo $anexoNoHash;?>">
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br/>
    </form>
</div>
<?php
    if (isset($_GET["action"]) && $_GET["action"] == 'list') {
?>
        <table id="roteiros-list" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class='titulo_coluna' >
                    <th>Roteiro</th>
                    <th>Tipo Roteiro</th>
                    <th>Data Início</th>
                    <th>Data Término</th>
                    <th>Responsável pelo roteiro</th>
                    <th>Estado</th>
                    <th>Cidade</th>
                    <th>Ativo</th>
                    <th>Status</th>
                    <th>Solicitante</th>
                    <th>Qtde Dias</th>
                    <th>Log</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $roteiros = getRoteiroList1();
                    foreach ($roteiros as $item) { 

                        $sql_t = "SELECT posto,cidade FROM tbl_roteiro_posto WHERE roteiro = {$item['roteiro']}";
                        $res_t = pg_query($con,$sql_t);

                        $estadoTabela = array();
                        $cidadeTabela = array();

                        for ($t=0; $t < pg_num_rows($res_t) ; $t++) { 
                            $posto_t = pg_fetch_result($res_t, $t, posto);
                            $cidade_t = pg_fetch_result($res_t, $t, cidade);
                            

                            if (!empty($posto_t)) {
                                $sql_tp = "SELECT contato_estado,contato_cidade FROM tbl_posto_fabrica WHERE posto = {$posto_t} AND fabrica = {$login_fabrica}; ";
                                $res_tp = pg_query($con,$sql_tp);
                                $estadoPostoTabela = pg_fetch_result($res_tp, 0, contato_estado);
                                $cidadePostoTabela = pg_fetch_result($res_tp, 0, contato_cidade);

                                if(!in_array($cidadePostoTabela, $cidadeTabela)){
                                    $cidadeTabela[]=$cidadePostoTabela;
                                }
                                if (!in_array($estadoPostoTabela, $estadoTabela)) {
                                    $estadoTabela[]=$estadoPostoTabela;
                                }

                            }
                            if (!empty($cidade_t)) {
                                $sql_tp = "SELECT estado,nome FROM tbl_cidade WHERE cidade = {$cidade_t};";
                                $res_tp = pg_query($con,$sql_tp);
                                $estadoCidadeTabela = pg_fetch_result($res_tp, 0, estado);
                                $cidadeCidadeTabela = pg_fetch_result($res_tp, 0, nome);

                                if (!in_array($cidadeCidadeTabela, $cidadeTabela)) {
                                    $cidadeTabela[]=$cidadeCidadeTabela;
                                }

                                if (!in_array($estadoCidadeTabela, $estadoTabela)) {
                                    $estadoTabela[]=$estadoCidadeTabela;
                                }                           
                            }
                        }
                    
                        $estadoTabela = implode(" / ", $estadoTabela);
                        $cidadeTabela = implode(" / ", $cidadeTabela);

                        if ($item['ativo'] == 't') {
                            $ativoTabela = 'Ativo';
                        } else {
                            $ativoTabela = 'Inativo';
                        }

                ?>
    
                        <tr>
                            <td style="text-align:'center';"><a href="<?php echo $_SERVER['PHP_SELF'] . '?roteiro=' . $item['roteiro'] ?>"><?php echo $item['roteiro'] ?><a></td>
                            <?php
                            $roteiroTipo = $item['tipo_roteiro'] == "RA" ? "Roteiro Administrativo" : "Roteiro Técnico";
                            
                            ?>
                            <td style="text-align:'center';"><?php echo $roteiroTipo ?></td>
                            <td style="text-align:'center';"><?php echo $item['data_inicio'] ?></td>
                            <td style="text-align:'center';"><?php echo $item['data_termino'] ?></td>
                            <td style="text-align:'center';">
                                <?php echo current($item["tecnico"])["nome"];?>
                            </td>
                            <td style="text-align:'center';"><?php echo $estadoTabela ?></td>
                            <td style="text-align:'center';"><?php echo $cidadeTabela ?></td>
                            <td style="text-align:'center';"><?php echo $ativoTabela ?></td>
                            <td style="text-align:'center';"><?php echo $item['status_descricao'] ?></td>
                            <td style="text-align:'center';"><?php echo $item['solicitante'] ?></td>
                            <td style="text-align:'center';"><?php echo $item['qtde_dias'] ?></td>
                            <td class="tac">
                                <a class="show-log btn btn-primary" href="#" data-object="roteiro" data-title="CADASTRO DE ROTEIRO" data-value="<?php echo $login_fabrica;?>*<?php echo $item['roteiro'];?>">Log</a>
                            </td>
                        </tr>
                        <?php } ?>
    
                            </tbody>
                        </table>
    
                        <?php
                    }?>
                    <script type="text/javascript">
                        $.dataTableLoad({
                            table : "#roteiros-list",
                            type: "full",
                            "aaSorting": []
                        });
                    </script>



<?php
function deletar_tabelas2($tblRoteiro,$idRoteiro){
    global $con;

    $sql = "DELETE FROM {$tblRoteiro} WHERE roteiro = {$idRoteiro} ";
    $res = pg_query($con,$sql);
}
function getEstados1(){
    global $con;

    $sql = "SELECT DISTINCT tbl_estado.estado, nome 
    FROM tbl_ibge 
    INNER JOIN tbl_estado ON tbl_estado.estado = tbl_ibge.estado
    ORDER BY nome ASC";
    $res = pg_query($con, $sql);
    return pg_fetch_all($res);


}

function getStatusRoteiro1(){
    global $con;

    $sql = "SELECT status_roteiro, descricao
    FROM tbl_status_roteiro 
    ORDER BY status_roteiro ASC";
    $res = pg_query($con, $sql);
    return pg_fetch_all($res);

}
function getCidades1($estado){
    global $con;

    $estado_in = implode("','", $estado);
    $estado_in ="'".$estado_in."'";

    $sql = "SELECT cod_ibge, estado||' - '||cidade as cidade 
    FROM tbl_ibge 
    WHERE estado in ( $estado_in ) AND 
    cidade IS NOT NULL
    ORDER BY cidade ASC";

    $res = pg_query($con, $sql);
    return pg_fetch_all($res);
}

function validateFields1(){
    global $login_fabrica, $edit;

    $msg_erro=null; 
    if(empty($_POST["data_inicio"])){
        $msg_erro["campos"][] = "data";
        $msg_erro["msg"]["obg"] =  "Preencha os campos data_inicio obrigatórios";
    }

    if(empty($_POST["data_termino"])){
        $msg_erro["campos"][] = "data";
        $msg_erro["msg"]["obg"] =  "Preencha os campos data_termino obrigatórios";
    }

    if (empty($_POST["tipo_roteiro"])) {
        $msg_erro["campos"][] = "tipo_roteiro";
        $msg_erro["msg"]["obg"] =  "Preencha os campos tipo_roteiro obrigatórios";
    }

    if (empty($_POST["solicitante"])) {
        $msg_erro["campos"][] = "solicitante";
        $msg_erro["msg"]["obg"] =  "Preencha os campos solicitante obrigatórios";
    }       

    if (empty($_POST["status_roteiro"])) {
        $msg_erro["campos"][] = "status_roteiro";
        $msg_erro["msg"]["obg"] =  "Preencha os campos status_roteiro obrigatórios";
    }
    if (empty($_POST["qtde_dias"])) {
        $msg_erro["campos"][] = "qtde_dias";
        $msg_erro["msg"]["obg"] =  "Preencha os campos qtde_dias obrigatórios";
    }

    if (empty($_POST["posto"]) && !$edit) {
        $msg_erro["campos"][] = "posto";
        $msg_erro["msg"]["obg"] =  "Preencha os campos posto obrigatórios";
    } 
    if (empty($_POST["tecnico"])) {
        $msg_erro["campos"][] = "tecnico";
        $msg_erro["msg"]["obg"] =  "Preencha os campos tecnico obrigatórios";
    }


    $dataInicio  = DateTime::createFromFormat("d/m/Y", $_POST["data_inicio"]);
    $dataTermino = DateTime::createFromFormat("d/m/Y", $_POST["data_termino"]);
    if($dataInicio > $dataTermino){
        $msg_erro["campos"][] = "data";
        $msg_erro["msg"][] =  "Data Inicial deve ser menor que Data Final"; 
    }

    return $msg_erro;
}

function getRoteiroList1(){
    global $con;
    global $login_fabrica;

    $sql = "SELECT  tbl_roteiro.roteiro,
                    tbl_roteiro.tipo_roteiro,
                    tbl_roteiro.ativo,
                    data_inicio,
                    data_termino,
                    tbl_status_roteiro.status_roteiro,
                    tbl_status_roteiro.descricao as status_descricao,
                    solicitante,
                    qtde_dias,
                    excecoes,
                    tbl_roteiro_posto.roteiro_posto,
                    tbl_roteiro_tecnico.roteiro_tecnico,
                    tbl_tecnico.cpf,
                    tbl_tecnico.nome

            FROM tbl_roteiro
            INNER JOIN tbl_roteiro_posto ON tbl_roteiro_posto.roteiro = tbl_roteiro.roteiro
            left JOIN tbl_roteiro_tecnico ON tbl_roteiro_tecnico.roteiro = tbl_roteiro.roteiro
            left JOIN tbl_status_roteiro ON tbl_status_roteiro.status_roteiro = tbl_roteiro.status_roteiro
            left JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_roteiro_tecnico.tecnico AND
                                      tbl_tecnico.fabrica = tbl_roteiro.fabrica
              WHERE tbl_roteiro.fabrica = $1
                ORDER BY data_inicio                                 ";

    $res = pg_query_params($con, $sql, array($login_fabrica));

    return groupResults1(pg_fetch_all($res));

}

function getRoteiro1($roteiro){
    global $con;
    global $login_fabrica;

    $sql = "SELECT  tbl_roteiro.roteiro,
                    tbl_roteiro.tipo_roteiro,
                    tbl_roteiro.admin,
                    tbl_roteiro.ativo,
                    tbl_roteiro_tecnico.tecnico as tecnicoid,
                    data_inicio,
                    data_termino,
                    tbl_status_roteiro.status_roteiro,
                    tbl_status_roteiro.descricao as status_descricao,
                    solicitante,
                    qtde_dias,
                    tbl_roteiro_posto.cidade,
                    excecoes,
                    tbl_roteiro_posto.roteiro_posto,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome as descricao_posto,
                    tbl_roteiro_tecnico.roteiro_tecnico,
                    tbl_tecnico.cpf,
                    tbl_tecnico.nome

            FROM tbl_roteiro
            LEFT JOIN tbl_roteiro_posto ON tbl_roteiro_posto.roteiro = tbl_roteiro.roteiro
            LEFT JOIN tbl_roteiro_tecnico ON tbl_roteiro_tecnico.roteiro = tbl_roteiro.roteiro
            LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_roteiro_posto.posto AND
                                            tbl_posto_fabrica.fabrica = tbl_roteiro.fabrica
            LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            LEFT JOIN tbl_status_roteiro ON tbl_status_roteiro.status_roteiro = tbl_roteiro.status_roteiro
            JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_roteiro_tecnico.tecnico AND
                                      tbl_tecnico.fabrica = tbl_roteiro.fabrica
            WHERE tbl_roteiro.roteiro = $1 ";

    $res = pg_query_params($con, $sql, array($roteiro));

    return groupResults1(pg_fetch_all($res));

}

function groupResults1($data){
    $roteiro = array();
    
    foreach($data as $item){
        $roteiro[$item["roteiro"]]["roteiro"] = $item["roteiro"];
        $roteiro[$item["roteiro"]]["admin"] = $item["admin"];
        $roteiro[$item["roteiro"]]["tipo_roteiro"] = $item["tipo_roteiro"];
        $roteiro[$item["roteiro"]]["ativo"] = $item["ativo"];
        $roteiro[$item["roteiro"]]["tipo_roteiro"] = $item["tipo_roteiro"];
        $roteiro[$item["roteiro"]]["tecnicoid"] = $item["tecnicoid"];
        $dataInicio = new DateTime($item["data_inicio"]);
        $roteiro[$item["roteiro"]]["data_inicio"] = $dataInicio->format("d/m/Y");

        $dataTermino = new DateTime($item["data_termino"]);
        $roteiro[$item["roteiro"]]["data_termino"] = $dataTermino->format("d/m/Y");

        $roteiro[$item["roteiro"]]["solicitante"] = $item["solicitante"];
        $roteiro[$item["roteiro"]]["qtde_dias"] = $item["qtde_dias"];
        $roteiro[$item["roteiro"]]["status_roteiro"] = $item["status_roteiro"];
        $roteiro[$item["roteiro"]]["status_descricao"] = $item["status_descricao"];
        
        $roteiro[$item["roteiro"]]["excecoes"] = $item["excecoes"];
        
        $roteiro[$item["roteiro"]]["tecnico"][$item["roteiro_tecnico"]] = groupTecnicos1($item);
        $roteiro[$item["roteiro"]]["posto"][$item["roteiro_posto"]] = groupPostos1($item);
    }


    return $roteiro;
}

function groupTecnicos1($item){
    return array("roteiro_tecnico"=> $item["roteiro_tecnico"],
                 "cpf"=> $item["cpf"],
                 "nome"=> $item["nome"] );
}

function groupPostos1($item){
return array("roteiro_posto"=> $item["roteiro_posto"],
                 "codigo_posto"=> $item["codigo_posto"],
                 "descricao_posto"=> $item["descricao_posto"],
                 "cidade"=> $item["cidade"], ); 
}

function getRoteioPosto($roteiro = null, $codigo = null) {
    global $con;
    global $login_fabrica;

    if (empty($roteiro) && !empty($codigo)) {
        $cond = " AND tbl_roteiro_posto.codigo = $codigo ";
    } else {
        $cond = " AND tbl_roteiro_posto.roteiro = $roteiro ";
    }

    $sql = "SELECT  tbl_roteiro_posto.roteiro_posto,
                    tbl_roteiro_posto.roteiro,
                    tbl_roteiro_posto.posto,
                    tbl_roteiro_posto.cidade,
                    tbl_roteiro_posto.qtde_horas,
                    tbl_roteiro_posto.tipo_de_visita,
                    tbl_roteiro_posto.tipo_de_local,
                    tbl_roteiro_posto.data_visita,
                    tbl_roteiro_posto.codigo,
                    tbl_roteiro_posto.contato,
                    tbl_roteiro_posto.status,
                    tbl_posto.nome
            FROM tbl_roteiro_posto
            JOIN tbl_posto ON tbl_posto.cnpj = tbl_roteiro_posto.codigo
            JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
           WHERE tbl_roteiro_posto.tipo_de_local = 'PA'
           {$cond}";
    $res = pg_query($con, $sql);

    return pg_fetch_all($res);

}

function getPosto($cnpj) {
    global $con;
    global $login_fabrica;

    $sql = "SELECT  tbl_posto.cnpj as codigo,
                    tbl_posto.nome
            FROM tbl_posto_fabrica
            JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
           WHERE tbl_posto.cnpj = '$cnpj'
           AND tbl_posto_fabrica.fabrica = {$login_fabrica}";

    $res = pg_query($con, $sql);

    return pg_fetch_array($res);

}


function getAdmins() {
    global $con;
    global $login_fabrica;

    $sql = "SELECT  admin, nome_completo
              FROM tbl_admin
             WHERE tbl_admin.ativo IS TRUE
               AND tbl_admin.fabrica = {$login_fabrica} ORDER BY nome_completo ASC";

    $res = pg_query($con, $sql);
    return pg_fetch_all($res);
}

function getTecnicos() {
    global $con;
    global $login_fabrica;

    $sql = "SELECT  tecnico, nome
              FROM tbl_tecnico
             WHERE tbl_tecnico.ativo IS TRUE
             AND tipo_tecnico = 'TF'
               AND tbl_tecnico.fabrica = {$login_fabrica} ORDER BY nome ASC";

    $res = pg_query($con, $sql);
    return pg_fetch_all($res);
}

function getRoteioCliente($roteiro = null, $codigo = null) {
    global $con;
    global $login_fabrica;
    if (empty($roteiro) && !empty($codigo)) {
        $cond = " AND tbl_roteiro_posto.codigo = '$codigo' ";
    } else {
        $cond = " AND tbl_roteiro_posto.roteiro = $roteiro ";
    }
    $sql = "SELECT  tbl_roteiro_posto.roteiro_posto,
                    tbl_roteiro_posto.roteiro,
                    tbl_roteiro_posto.cidade,
                    tbl_roteiro_posto.qtde_horas,
                    tbl_roteiro_posto.tipo_de_visita,
                    tbl_roteiro_posto.tipo_de_local,
                    tbl_roteiro_posto.data_visita,
                    tbl_roteiro_posto.codigo,
                    tbl_roteiro_posto.contato,
                    tbl_roteiro_posto.status,
                    tbl_cliente.nome
            FROM tbl_roteiro_posto
            JOIN tbl_cliente ON tbl_cliente.cpf = tbl_roteiro_posto.codigo
           WHERE tbl_roteiro_posto.tipo_de_local = 'CL'
             {$cond}";

    $res = pg_query($con, $sql);
    return pg_fetch_all($res);

}

function getCliente($cpf) {
    global $con;
    global $login_fabrica;

    $sql = "SELECT  cpf as codigo,
                    nome
            FROM tbl_cliente
           WHERE cpf = '$cpf'";
    $res = pg_query($con, $sql);
    return pg_fetch_array($res);

}

function getRoteioRevenda($roteiro = null, $codigo = null) {
    global $con;
    global $login_fabrica;
    if (empty($roteiro) && !empty($codigo)) {
        $cond = " AND tbl_roteiro_posto.codigo = $codigo ";
    } else {
        $cond = " AND tbl_roteiro_posto.roteiro = $roteiro ";
    }

    $sql = "SELECT  tbl_roteiro_posto.roteiro_posto,
                    tbl_roteiro_posto.roteiro,
                    tbl_roteiro_posto.cidade,
                    tbl_roteiro_posto.qtde_horas,
                    tbl_roteiro_posto.tipo_de_visita,
                    tbl_roteiro_posto.data_visita,
                    tbl_roteiro_posto.tipo_de_local,
                    tbl_roteiro_posto.codigo,
                    tbl_roteiro_posto.contato,
                    tbl_roteiro_posto.status,
                    tbl_revenda.nome
            FROM tbl_roteiro_posto
            JOIN tbl_revenda ON tbl_revenda.cnpj = tbl_roteiro_posto.codigo
           WHERE tbl_roteiro_posto.tipo_de_local = 'RV'
              {$cond}";

    $res = pg_query($con, $sql);

    return pg_fetch_all($res);

}

function getRevenda($codigo) {
    global $con;
    global $login_fabrica;

    $sql = "SELECT  tbl_revenda.cnpj as codigo,
                    tbl_revenda.nome
            FROM tbl_revenda
           WHERE tbl_revenda.cnpj = '$codigo'";

    $res = pg_query($con, $sql);

    return pg_fetch_array($res);

}

function getAssunto($codigo) {
    global $con, $login_fabrica;

    $sql = "SELECT  assunto
            FROM tbl_roteiro_assunto
            WHERE roteiro_assunto = $codigo
            AND fabrica = $login_fabrica";

    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        return utf8_decode(pg_fetch_result($res, 0, "assunto"));
    } else {
        return '';
    }
}

function getAssuntoCompleto($contato) {

    unset($assunto_array);
    $assunto_completo = [];

    $assunto_array = $contato;
    $assunto_array = str_replace('\\\\u', '\\u', $assunto_array);
    $assunto_array = json_decode($assunto_array, true);
    $assunto_array_pronto = "";

    foreach ($assunto_array as $key => $value) {
        if (isset($value['assunto'])) {
            $assunto_array_pronto['assunto'] = $value['assunto']; 
        }

        if (isset($value['assunto_add'])) {
            $assunto_array_pronto['assunto_add'] = $value['assunto_add']; 
        }
    }

    $assunto_completo[] = ["assunto"=>$assunto_array_pronto["assunto"], "assunto_html"=>getAssunto($assunto_array_pronto["assunto"]), "assunto_add"=>utf8_decode($assunto_array_pronto["assunto_add"])];

    return $assunto_completo;
}

function getLegendaTipoVisita($sigla) {
    $aa = array("VT" => "Visita Técnica","VC" => "Visita Comercial","VA" => "Visita Administrativa","CM" => "Clínica Makita","FE" => "Feira/Evento","TN" => "Treinamento");
     return $aa[$sigla];
}

function getLegendaTipoContato($sigla) {
    return array("CL" => "Cliente","RV" => "Revenda","PA" => "Posto Autorizado");
}

function getLegendaStatus($sigla) {
    return array("AC" => "A Confirmar", "CF" => "Confirmado", "OK" => "Visita feita", "CC" => "Cancelado");
}


function getLegendaByStatus($sigla) {
    $aa = array("AC" => 1, "CF" => 2, "OK" => 3, "CC" => 4);
    return $aa[$sigla];
}

function getLegendaByIdStatus($sigla) {
    $aa = array(1 => "AC", 2 => "CF",3 => "OK",4 => "CC");
    return $aa[$sigla];
}


if ($_GET["externo"] == true && strlen($_GET['posto']) > 0) {

    echo '
        <script>
            $(function(){
                $("#data_termino").trigger("change");
            });
        </script>';
}
include "rodape.php";
