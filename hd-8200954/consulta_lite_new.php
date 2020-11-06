<?php
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

if ($areaAdmin === true) {
    $admin_privilegios = "call_center"; 
    include __DIR__.'/admin/autentica_admin.php';
    include_once('../class/tdocs.class.php');
} else {
    include __DIR__.'/autentica_usuario.php';
    include_once('class/tdocs.class.php');
}
include_once __DIR__.'/fn_traducao.php';
include_once __DIR__.'/funcoes.php';

if (file_exists(__DIR__."/os_cadastro_unico/fabricas/$login_fabrica/regras.php")) {
    include __DIR__."/os_cadastro_unico/fabricas/$login_fabrica/regras.php";
} else {
    include __DIR__."/os_cadastro_unico/fabricas/regras.php";
}

if (file_exists(__DIR__."/os_cadastro_unico/fabricas/$login_fabrica/regras_consulta_os.php")) {
    include __DIR__."/os_cadastro_unico/fabricas/$login_fabrica/regras_consulta_os.php";
} else {
    include __DIR__."/os_cadastro_unico/fabricas/regras_consulta_os.php";
}

//VERIFICA POSTO INTERNO
if($areaAdmin == false){
    $sqlPostoInterno = "SELECT posto_interno FROM tbl_posto_fabrica INNER JOIN tbl_tipo_posto USING(tipo_posto,fabrica) WHERE tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = $login_posto";
    $pg_res = pg_query($con, $sqlPostoInterno);
    $posto_interno = pg_fetch_result($pg_res, 0, 'posto_interno');
}

// TIPO DE ATENDIMENTO
$sqlTipoAtendimento = "SELECT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao";
$pg_res = pg_query($con, $sqlTipoAtendimento);
$listaDeTiposDeAtendimentos = pg_fetch_all($pg_res);

// STATUS DA OS
$sqlStatusdaOS = "SELECT status_checkpoint, descricao, cor FROM tbl_status_checkpoint WHERE status_checkpoint IN ({$cons_status_checkpoint})";
$pg_res = pg_query($con, $sqlStatusdaOS);
$listaDeStatusDaOS = pg_fetch_all($pg_res); 

// LINHA
$sqlLinha = "SELECT linha, nome from tbl_linha where fabrica = $login_fabrica and ativo = true order by nome";
$pg_res = pg_query($con, $sqlLinha);
$listaDeLinhas = pg_fetch_all($pg_res);

// FAMILIA
$sqlFamilia = "SELECT familia, descricao from tbl_familia where fabrica = $login_fabrica and ativo = true order by descricao";
$pg_res = pg_query($con, $sqlFamilia);
$listaDeFamilias = pg_fetch_all($pg_res);  

// ESTADOS
$listaDeEstadosDoBrasil = [
    "AC" => "AC - Acre",
    "AL" => "AL - Alagoas",
    "AM" => "AM - Amazonas",
    "AP" => "AP - Amapá",
    "BA" => "BA - Bahia",
    "CE" => "CE - Ceará",
    "DF" => "DF - Distrito Federal",
    "ES" => "ES - Espírito Santo",
    "GO" => "GO - Goias",
    "MA" => "MA - Maranhão",
    "MG" => "MG - Minas Gerais",
    "MS" => "MS - Mato Grosso do Sul",
    "MT" => "MT - Mato Grosso",
    "PA" => "PA - Pará",
    "PB" => "PB - Paraíba",
    "PE" => "PE - Pernambuco",
    "PI" => "PI - Piauí",
    "PR" => "PR - Paraná",
    "RJ" => "RJ - Rio de Janeiro",
    "RN" => "RN - Rio Grande do Norte",
    "RO" => "RO - Rondônia",
    "RR" => "RR - Roraima",
    "RS" => "RS - Rio Grande do Sul",
    "SC" => "SC - Santa Catarina",
    "SE" => "SE - Sergipe",
    "SP" => "SP - São Paulo",
    "TO" => "TO - Tocantins"
];

if ($_POST["ajax"] && $_POST["ajax"] == "verificaGeraPedidoDevolucao") { 

    $os             = $_POST["os"];
    $gera_pedido    = "false";
    $troca_produto  = "false";
    $retorno        = [];

    $sql_os = "SELECT tbl_os.posto,tbl_tipo_atendimento.descricao
            FROM tbl_os
            JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = tbl_os.fabrica
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
            AND tbl_tipo_posto.posto_interno IS TRUE
            WHERE tbl_os.os = {$os}";

    $res_os = pg_query($con, $sql_os);
    if (pg_num_rows($res_os) > 0) {
        $tipo_atendimento = pg_fetch_result($res_os, 0, 'descricao');
        if ($tipo_atendimento == "Orçamento") {
            $left = "LEFT ";
        }
        $sql = "SELECT  tbl_os_item.pedido_item,
                        tbl_servico_realizado.gera_pedido,
                        tbl_servico_realizado.troca_produto
                    FROM tbl_os_item
                    INNER JOIN tbl_os_produto on tbl_os_item.os_produto = tbl_os_produto.os_produto
                    INNER JOIN tbl_os on tbl_os_produto.os = tbl_os.os
                    $left JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                    LEFT JOIN tbl_pedido_item on tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
                    WHERE tbl_os.os = $os
                    AND tbl_os.fabrica = $login_fabrica ";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0){

            $result = pg_fetch_all($res);

            foreach ($result as $key => $value) {
                if((!strlen($value["pedido_item"]) or $value["pedido_item"] == NULL ) and $value["gera_pedido"] == "t" ) {
                    $gera_pedido[] = "true";
                }
                if($value["troca_produto"] == "t"){
                    $troca_produto[] = "true";
                }
            }

            if ($troca_produto == "t") {
                $retorno = ["erro" => true, "msg" => "Item gera troca"];
            } elseif ($gera_pedido == "t") {
                $retorno = ["erro" => true, "msg" => "Item gera pedido"];
            } else {
                $retorno = ["erro" => false, "msg" => utf8_encode("Deseja gerar um Pedido de Devolução?")];
            }

        } else {
            $retorno = ["erro" => false, "msg" => utf8_encode("Deseja gerar um Pedido de Devolução?")];
        }

    } else {
        $retorno = ["erro" => true, "msg" => utf8_encode("Os não é de posto interno")];
    }

    exit(json_encode($retorno));

}

if ($_POST["ajax"] && $_POST["ajax"] == "consertado") { 

    $os = $_POST["os"];
    $msg_erro = "";

    $sql = "SELECT  tbl_os.os,
                    tbl_os.os_numero,
                    tbl_os.os_sequencia,                    
                    tbl_produto.linha
             FROM tbl_os
             JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
            WHERE os = $os";

    $res = pg_query($con,$sql);

    if(pg_num_rows($res) == 0) {
        $msg_erro = "Erro ao consertar a O.S.";
        $retorno = ["erro" => true, "msg" => $msg_erro];
    } else {
        if (strlen($msg_erro) == 0) {
            $res_consertado = pg_query ($con,"BEGIN TRANSACTION");

            $sql = " UPDATE tbl_os
                        SET data_conserto = CURRENT_TIMESTAMP
                      WHERE os = $os";
            $res = pg_query($con,$sql);

            if (pg_last_error()) {
                $msg_erro = "Erro ao consertar a O.S.";
            }

            if (strlen($msg_erro) == 0) {
                $res_consertado = pg_query ($con,"COMMIT TRANSACTION");
                $retorno = ["erro" => false];

                if(in_array($login_fabrica,array(186))){
                    envia_email_consumidor_status_os(); 
                }
            } else {
                $res_consertado = pg_query ($con,"ROLLBACK TRANSACTION");
                $retorno = ["erro" => true, "msg" => $msg_erro];
            }
        }
    }

    exit(json_encode($retorno));
}

if ($_POST["ajax"] && $_POST["ajax"] == "excluir") { 
  
    $os = $_POST["os"];

    $sql = "SELECT tbl_os.os
              FROM tbl_os
             WHERE os = $os ";

    $res = pg_query($con,$sql);

    if(pg_num_rows($res) == 0) {
        $msg_erro = "Não foi possível excluir a OS";
        $retorno = ["erro" => true, "msg" => $msg_erro];
    } else {
        $os = pg_fetch_result($res, 0, os);        

        $res = pg_query ($con,"BEGIN TRANSACTION");
        /**
         * Exclui os arquivos em anexo, se tiver
         **/
        include_once 'anexaNF_inc.php';
        if (count($anexos = temNF($os, 'path'))) { 
            foreach ($anexos as $arquivoAnexo) {
                excluirNF($arquivoAnexo);
            }
        }  

        $sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
        $res = @pg_query($con, $sql);
        $msg_erro = pg_errormessage($con);

        if (strlen ($msg_erro) == 0) {
            $res = pg_query ($con,"COMMIT TRANSACTION");
            $retorno = ["erro" => false];
        } else {
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
            $retorno = ["erro" => true, "msg" => $msg_erro];
        }
    }
    exit(json_encode($retorno));

}

if ($_POST["ajax_cancelar_os"]) {
    try {
        $os        = $_POST["os"];
        $mesagem   = trim($_POST["mensagem"]);
        $continuar = $_POST["continuar"];

        if ($continuar == "false") {
            $continuar = false;
        }

        if (empty($os)) {
            throw new Exception("Erro ao cancelar: Ordem de Serviço não encontrada");
        }

        if (empty($mensagem)) {
            throw new Exception("Erro ao cancelar: Motivo não informado");
        }

        $sql = "
            SELECT sua_os, posto FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}
        ";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception("Erro ao cancelar: Ordem de Serviço não encontrada");
        }

        $sua_os = pg_fetch_result($res, 0, "sua_os");
        $posto  = pg_fetch_result($res, 0, "posto");

        if (!$continuar) {
            $sql = "
                SELECT *
                FROM tbl_os_item
                INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
                INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido AND tbl_pedido.fabrica = {$login_fabrica}
                WHERE tbl_os_produto.os = {$os}
                AND (tbl_pedido_item.qtde - (COALESCE(tbl_pedido_item.qtde_faturada, 0) + COALESCE(tbl_pedido_item.qtde_cancelada, 0))) > 0
                AND tbl_pedido.status_pedido NOT IN(1)
            ";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                exit(json_encode(array("continuar" => utf8_encode("A OS possui peças aguardando a emissão de nota fiscal, também é necessário o cancelamento do pedido da peça no ERP para evitar futuros erros na emissão de nota fiscal, deseja continuar com o cancelamento ?"))));
            }
        }

        pg_query($con, "BEGIN");

        $transaction = true;

        $sql = "
            UPDATE tbl_os SET
                excluida = TRUE,
                status_checkpoint = 28,
                admin_excluida = $login_admin
            WHERE fabrica = {$login_fabrica}
            AND os = {$os}
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao cancelar ordem de serviço");
        }

        $sql = "
            INSERT INTO tbl_os_interacao
            (os, data, admin, comentario, interno, fabrica)
            VALUES
            ({$os}, CURRENT_TIMESTAMP, {$login_admin}, E'Ordem de Serviço cancelada pela fábrica: {$mensagem}', false, $login_fabrica)
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao cancelar ordem de serviço");
        }

        $sql = "
            INSERT INTO tbl_comunicado
            (mensagem, descricao, tipo, fabrica, obrigatorio_site, posto, ativo)
            VALUES
            (E'{$mensagem}', 'Ordem de Serviço cancelada - $sua_os', 'Comunicado', $login_fabrica, true, $posto, true)
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao cancelar ordem de serviço");
        }

        $sql = "
            UPDATE tbl_auditoria_os SET
                cancelada = CURRENT_TIMESTAMP,
                admin = {$login_admin},
                justificativa = 'Ordem de Serviço cancelada'
            WHERE os = {$os}
            AND liberada IS NULL AND reprovada IS NULL AND cancelada IS NULL
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao cancelar ordem de serviço");
        }

        $sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = $login_fabrica AND LOWER(descricao) = 'cancelado'";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception("Erro ao cancelar ordem de serviço");
        }

        $servico_realizado = pg_fetch_result($res, 0, "servico_realizado");

        $sql = "
            SELECT oi.os_item, oi.pedido_item, pi.pedido, (pi.qtde - (COALESCE(pi.qtde_faturada, 0) + COALESCE(pi.qtde_cancelada, 0))) AS qtde_pendente
            FROM tbl_os_item oi
            INNER JOIN tbl_os_produto op ON op.os_produto = oi.os_produto
            LEFT JOIN tbl_pedido_item pi ON pi.pedido_item = oi.pedido_item
            WHERE op.os = {$os}
        ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            foreach (pg_fetch_all($res) as $i => $row) {
                $row = (object) $row;

                if (!empty($row->pedido_item) && $row->qtde_pendente == 0) {
                    continue;
                }

                $update = "
                    UPDATE tbl_os_item SET
                        servico_realizado = {$servico_realizado}
                    WHERE os_item = {$row->os_item}
                ";
                $resUpdate = pg_query($update);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao cancelar ordem de serviço");
                }

                if (!empty($row->pedido_item) && $row->qtde_pendente > 0) {
                    $update = "
                        UPDATE tbl_pedido_item SET
                            qtde_cancelada = {$row->qtde_pendente}
                        WHERE pedido_item = {$row->pedido_item};

                        SELECT fn_atualiza_status_pedido({$login_fabrica}, {$row->pedido});
                    ";
                    $resUpdate = pg_query($con, $update);

                     if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao cancelar ordem de serviço");
                    }
                }
            }
        }

        if ($usaMobile) {
            $cockpit = new \Posvenda\Cockpit($login_fabrica);
            $cockpit->cancelaOsMobile($os, $con);
        }

        pg_query($con, "COMMIT");

        exit(json_encode(array("sucesso" => true)));
    } catch (Exception $e) {
        if ($transaction) {
            pg_query($con, "ROLLBACK");
        }

        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

if ($_POST["ajax_reabrir_os"]) {
    try {
        $os      = $_POST["os"];
        $mesagem = trim($_POST["mensagem"]);

        if (empty($os)) {
            throw new Exception("Erro ao reabrir: Ordem de Serviço não encontrada");
        }

        if (empty($mensagem)) {
            throw new Exception("Erro ao reabrir: Motivo não informado");
        }

        $sql = "
            SELECT sua_os, posto FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}
        ";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception("Erro ao reabrir: Ordem de Serviço não encontrada");
        }

        $sua_os = pg_fetch_result($res, 0, "sua_os");
        $posto  = pg_fetch_result($res, 0, "posto");

        pg_query($con, "BEGIN");

        $transaction = true;

        $sql = "
            UPDATE tbl_os SET
                excluida = false
            WHERE fabrica = {$login_fabrica}
            AND os = {$os}
        ";

        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("1Erro ao reabrir ordem de serviço");
        }

        $sql = "
            INSERT INTO tbl_os_interacao
            (os, data, admin, comentario, interno, fabrica)
            VALUES
            ({$os}, CURRENT_TIMESTAMP, {$login_admin}, E'Ordem de Serviço reaberta pela fábrica: {$mensagem}', false, $login_fabrica)
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("2Erro ao reabrir ordem de serviço");
        }
 
        $sql = "
            INSERT INTO tbl_comunicado
            (mensagem, descricao, tipo, fabrica, obrigatorio_site, posto, ativo)
            VALUES
            (E'{$mensagem}', 'Ordem de Serviço reaberta - $sua_os', 'Comunicado', $login_fabrica, true, $posto, true)
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("3Erro ao reabrir ordem de serviço");
        }

        pg_query($con, "COMMIT");

        exit(json_encode(array("sucesso" => true)));
    } catch (Exception $e) {
        if ($transaction) {
            pg_query($con, "ROLLBACK");
        }

        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

if ($_POST["ajax"] && $_POST["ajax"] == "excluir_pre_os") {

    $hd_chamado = $_POST['hd_chamado'];

    $sql_interacao = "INSERT INTO tbl_hd_chamado_item (hd_chamado, data, comentario, status_item) VALUES ($hd_chamado, current_timestamp, 'Pré-atendimento cancelado pelo Posto', 'Resolvido')";
    $res_interacao = pg_query($con, $sql_interacao);
    $msg_erro = pg_errormessage($con);

    if(strlen($msg_erro) == 0){
        $sql_status = "UPDATE tbl_hd_chamado SET status = 'Resolvido' WHERE hd_chamado = $hd_chamado AND fabrica = $login_fabrica ; UPDATE tbl_hd_chamado_extra SET abre_os = FALSE where hd_chamado = $hd_chamado ; ";
        $res_status = pg_query($con, $sql_status);
        $msg_erro = pg_errormessage($con);
    }

    if(strlen($msg_erro) > 0){
        $retorno['erro'][] = "ok";
    }else{
        $retorno['sucesso'] = "ok";
    }

    echo json_encode($retorno);
    exit;
}

if ($_GET['ajax'] == 'busca_laudo_tecnico_os') {
    try {
        $os = $_GET['os'];
        
        if (empty($os)) {
            throw new \Exception('Ordem de Serviço não informada');
        }
        $condPosto = ($areaAdmin === false) ? " AND o.posto = {$login_posto}" : "";
        $sql = "
            SELECT tco.titulo, tco.observacao, o.sua_os
            FROM tbl_laudo_tecnico_os tco
            INNER JOIN tbl_os o ON o.os = tco.os AND o.fabrica = {$login_fabrica}
            WHERE tco.fabrica = {$login_fabrica}
            {$condPosto}
            AND tco.os = {$os}
        ";
        $res = pg_query($con, $sql);
        
        if (!pg_num_rows($res)) {
            throw new \Exception('Ordem de Serviço inválida');
        }
        
        $laudo_tecnico = pg_fetch_assoc($res);
        $laudo_tecnico = array_map('utf8_encode',$laudo_tecnico);
        exit(json_encode($laudo_tecnico));
    } catch(\Exception $e) {
        exit(json_encode(array('erro' => utf8_encode($e->getMessage()))));
    }
}

if ($_GET['ajax'] == 'busca_laudo_tecnico') {
    try {
        $os = $_GET['os'];
        
        if (empty($os)) {
            throw new \Exception('Ordem de Serviço não informada');
        }
        
        $sql = "SELECT o.os, o.sua_os, ta.comentario
                  FROM tbl_os o 
            INNER JOIN tbl_laudo_tecnico ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica} 
           --INNER JOIN tbl_laudo_tecnico_os ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica} 
                 WHERE o.fabrica = {$login_fabrica} 
                   AND o.posto = {$login_posto} 
                   AND o.os = {$os}";
        $res = pg_query($con, $sql);
        
        if (!pg_num_rows($res)) {
            throw new \Exception('Ordem de Serviço inválida');
        }
        
        $laudo_tecnico = pg_fetch_assoc($res);

        $laudo_tecnico['comentario'] = utf8_encode($laudo_tecnico['comentario']);
        $laudo_tecnico['sua_os'] = $os['sua_os'];
        
        exit(json_encode($laudo_tecnico));
    } catch(\Exception $e) {
        exit(json_encode(array('erro' => utf8_encode($e->getMessage()))));
    }
}


function mountSqlWithConditions(array $conditionsFields = null, array $joinsFields = null, array $propertiesFields = null, $distinct = null): string {
    global $login_fabrica, $login_posto;

    if (!empty($login_posto)) {
        $conditionsFields[] = "AND tbl_posto_fabrica.posto=".$login_posto;
    }

    $sql = "SELECT $distinct
            {properties}
            tbl_os.status_checkpoint,
            tbl_os.os_reincidente,
            tbl_os.os,
            tbl_os.fabrica, 
            tbl_os.sua_os, 
            tbl_os.nota_fiscal,
            tbl_os.excluida, 
            tbl_os.os_numero,
            --tbl_os_troca.troca_revenda,
            TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura,
            TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento,
            TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY') as data_nf,
            replace(tbl_os_produto.serie, 'AÃƒÂ§ÃƒÂ£o', '') as serie,
            tbl_os.consumidor_estado,
            tbl_os.consumidor_revenda,
            tbl_os.consumidor_nome,
            tbl_os.revenda_nome,
            tbl_os.tipo_atendimento,
            TO_CHAR(tbl_os.data_conserto, 'DD/MM/YYYY') AS data_conserto,
            tbl_os.finalizada,
            tbl_os.os_posto,
            tbl_tipo_atendimento.descricao, 
            tbl_posto.posto, 
            tbl_posto_fabrica.codigo_posto, 
            tbl_posto_fabrica.contato_estado, 
            tbl_posto_fabrica.contato_cidade, 
            tbl_os_extra.impressa, 
            tbl_os_extra.extrato,
            tbl_posto.nome AS posto_nome, 
            tbl_posto.estado, 
            tbl_produto.referencia AS produto_referencia, 
            tbl_produto.descricao AS produto_descricao, 
            tbl_produto.voltagem AS produto_voltagem, 
            status_os_ultimo AS status_os,
            tbl_status_os.descricao AS status_orcamento 
            FROM tbl_os 
            LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
            JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
            LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto and tbl_produto.fabrica_i = {$login_fabrica}

            --JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha and tbl_linha.fabrica = {$login_fabrica}
            --JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia and tbl_familia.fabrica = {$login_fabrica}

            JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os and tbl_os_extra.i_fabrica = {$login_fabrica}
            JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_os.fabrica and tbl_fabrica.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
            LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = {$login_fabrica}
            LEFT JOIN tbl_status_os ON tbl_os.status_os_ultimo = tbl_status_os.status_os
            --LEFT JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os
            {joins}
            WHERE tbl_os.fabrica = {$login_fabrica}
            AND (tbl_os_extra.status_os NOT IN (13,15) OR tbl_os_extra.status_os IS NULL) 
            AND (tbl_os.excluida IS NOT TRUE OR (tbl_os.excluida IS TRUE AND tbl_os.status_checkpoint = 28)) 
            {conditions}
            ORDER BY tbl_os.os DESC";

            // Verifica se existe condicoes, caso exista montra uma string para ser substituida no sql
            $conditions = ' ';
            if( $conditionsFields ){
                foreach($conditionsFields as $fieldWithCondition){
                    $conditions .= $fieldWithCondition . ' ';
                }
                $conditions .= ' ';
            }else{
                $conditions = null;
            }
            
            // Verifica se existe algum join, casoo exista monta uma string para ser substituida no sql
            $joins = '';
            if( $joinsFields ){
                foreach($joinsFields as $fieldWithCondition){
                    $joins .= $fieldWithCondition . ' ';
                }
                $joins .= ' ';
            }else{
                $joins = null;
            }

            // Verifica se existe alguma propertie, casoo exista monta uma string para ser substituida no sql
            $properties = '';
            if( $propertiesFields ){
                foreach($propertiesFields as $fieldWithCondition){
                    $properties .= $fieldWithCondition . ', ';
                }
            }else{
                $properties = null;
            }

            $sql = str_replace('{conditions}', $conditions, $sql);
            $sql = str_replace('{properties}', $properties, $sql);
            $sql = str_replace('{joins}', $joins, $sql);

            return $sql;
}

function mountCsv($data, $fields = null, $listaStatus = null, $cons_lista_de_legendas = null){
    global $defaultFieldsCsv,$login_fabrica;

    // Se nao foi passado nenhum header entao coloca os headers default
    if( $fields == null ){
        $fields = $defaultFieldsCsv;
    } 

    $arquivo_nome = "consulta-os-{$login_fabrica}-".date("dmYHis").".csv";

    $arquivo_completo = 'xls/' . $arquivo_nome;
    $caminho_download = 'xls/' . $arquivo_nome;

    // Inicia o arquivo
    $arquivo = fopen($arquivo_completo, "w+");

    // Monta o cabecalho do arquivo
    $header = array_values($fields);
    fputcsv($arquivo, $header, ";");

    // Itera e adiciona o conteudo
    foreach ($data as $row) {
        $resultRow = [];

        foreach ($fields as $key => $value) {
            if( $key == 'produto_descricao' ){
                $resultRow[$key] = $row['produto_referencia'] . ' - ' . $row['produto_descricao'];
                continue;
            }

            if( $key == 'posto_nome' ){
                $resultRow[$key] = $row['codigo_posto'] . ' - ' . $row['posto_nome'];
                continue; 
            }

            if( $key == 'status_checkpoint' ){
                if( !empty($listaStatus) ){
                    foreach ($listaStatus as $status) {
                        if( $status['status_checkpoint'] == $row['status_checkpoint'] ){
                            $resultRow[$key] = $status['descricao'];
                            break;
                        }
                    }
                }
                continue;
            }

            if( $key == 'situacao' ){
                $arrayInfo = verifySubtitles($row, $cons_lista_de_legendas);
                $resultRow[$key] = $arrayInfo['descricao'];
                continue;
            }


            $resultRow[$key] = $row[$key];             
        }

        fputcsv($arquivo, $resultRow, ";");
    }

    // fecha a stream com o arquivo
    fclose($arquivo);

    return $caminho_download;
}

function verifySubtitles(array $os, array $legendas) : array {
    global $con;

    $arraySubtitles = [];
    $data_abertura = DateTime::createFromFormat('d/m/Y', $os['abertura']);
    $data_intervalo = new DateTime('now');
    $diff = $data_abertura->diff($data_intervalo);

    // Verifica OS Reincidente
    if( $os['os_reincidente'] == 't' ) $arraySubtitles = $legendas[0];

    // Verifica OS aberta a mais de 25 dias e sem data de fechamento
    if( strlen($os['fechamento']) <= 0 AND $diff->days > 25 ) $arraySubtitles = $legendas[1];

    // Verifica OS reincidente e aberta a mais de 25 dias
    if( $diff->days > 25 AND $os['os_reincidente'] == 't' AND strlen($os['fechamento'] <= 0) ) $arraySubtitles = $legendas[2];

    // SQL para verificacoes abaixo
    $pg_res_ = pg_query($con, "SELECT os FROM tbl_os_troca WHERE os = {$os['os']}");
    $res = pg_fetch_all($pg_res_);

    // Verifica OS com troca de produto
    if( $res ) $arraySubtitles = $legendas[3];

    //Verifica OS com ressarcimento
    if( $res AND $res['ressarcimento'] == 't' ) $arraySubtitles = $legendas[4];

    return $arraySubtitles;
}

function validateOsDate( $data_os ){
    $data_os = DateTime::createFromFormat('d/m/Y', $data_os);
    $data_validacao = DateTime::createFromFormat('d/m/Y', '01/04/2019');

    return ($data_os > $data_validacao );
}

class FieldValidation extends Exception{}

// Recupera os campos do formulario enviados pelo metodo POST

if( $_REQUEST['action'] == 'formulario_pesquisa' ){
    $sua_os             = $_REQUEST['sua_os'];
    $serie              = $_REQUEST['serie'];
    $nf_compra          = $_REQUEST['nf_compra'];
    $consumidor_cpf     = $_REQUEST['consumidor_cpf'];
    $tipo_atendimento   = $_REQUEST['tipo_atendimento'];
    $status_checkpoint  = $_REQUEST['status_checkpoint'];
    $statusOrcamento    = $_REQUEST['status_orcamento'];
    $tipo_os            = $_REQUEST['tipo_os'];
    $linha              = $_REQUEST['linha'];
    $familia            = $_REQUEST['familia'];
    $data_tipo          = $_REQUEST['data_tipo'];
    $data_inicial       = $_REQUEST['data_inicial'];
    $data_final         = $_REQUEST['data_final'];
    $os_aberta          = $_REQUEST['os_aberta'];
    $estado             = $_REQUEST['estado'];
    $codigo_posto       = $_REQUEST['codigo_posto'];
    $nome_posto         = $_REQUEST['nome_posto'];
    $nome_consumidor    = $_REQUEST['nome_consumidor'];
    $referencia_produto = $_REQUEST['produto_referencia'];
    $descricao_produto  = $_REQUEST['produto_nome'];
    $os_situacao        = $_REQUEST['os_situacao'];
    $pre_os             = $_REQUEST['pre_os'];
    $revenda_cnpj       = $_REQUEST['revenda_cnpj'];
    $gerar_excel        = $_REQUEST['gerar_excel'];

/*


    $sua_os             = filter_input(INPUT_POST, 'sua_os', FILTER_SANITIZE_NUMBER_INT);
    $serie              = filter_input(INPUT_POST, 'serie');
    $nf_compra          = filter_input(INPUT_POST, 'nf_compra');
    $consumidor_cpf     = filter_input(INPUT_POST, 'consumidor_cpf');
    $tipo_atendimento   = filter_input(INPUT_POST, 'tipo_atendimento');
    $status_checkpoint  = filter_input(INPUT_POST, 'status_checkpoint');
    $tipo_os            = filter_input(INPUT_POST, 'tipo_os');
    $linha              = filter_input(INPUT_POST, 'linha');
    $familia            = filter_input(INPUT_POST, 'familia');
    $data_tipo          = filter_input(INPUT_POST, 'data_tipo');
    $data_inicial       = filter_input(INPUT_POST, 'data_inicial');
    $data_final         = filter_input(INPUT_POST, 'data_final');
    $os_aberta          = filter_input(INPUT_POST, 'os_aberta');
    $estado             = filter_input(INPUT_POST, 'estado');
    $codigo_posto       = filter_input(INPUT_POST, 'codigo_posto');
    $nome_posto         = filter_input(INPUT_POST, 'nome_posto');
    $nome_consumidor    = filter_input(INPUT_POST, 'nome_consumidor');
    $referencia_produto = filter_input(INPUT_POST, 'produto_referencia');
    $descricao_produto  = filter_input(INPUT_POST, 'produto_nome');
    $os_situacao        = filter_input(INPUT_POST, 'os_situacao');
    $pre_os             = filter_input(INPUT_POST, 'pre_os');
    $revenda_cnpj       = filter_input(INPUT_POST, 'revenda_cnpj');
    $gerar_excel        = filter_input(INPUT_POST, 'gerar_excel');
  
*/

  $joinsArray      = [];
    $propertiesArray = [];

    if (in_array($login_fabrica, [190])) {
        $propertiesFields[] = "tbl_contrato_os.contrato AS contrato_id";
        $joinsArray[] =  "LEFT JOIN tbl_contrato_os ON tbl_contrato_os.os = tbl_os.os";
    }

    if ($login_fabrica == 139) {
        $distinct = " DISTINCT ";
    }

    try{
        // Inicio das validacoes
        if( $data_inicial AND $data_final ){
            $data_inicial_temp = explode('/', $data_inicial);
            $data_final_temp   = explode('/', $data_final);

            // Validacao da data_inicial e data_final
            if( !checkdate($data_inicial_temp[1], $data_inicial_temp[0], $data_inicial_temp[2]) OR !checkdate($data_final_temp[1], $data_final_temp[0], $data_final_temp[2]) ) {
                $data_inicial = $data_final = null;
                throw new FieldValidation(traduz("Data Inicial ou Final Inválida"));
            } else {
                $data_inicial = DateTime::createFromFormat('d/m/Y', $data_inicial); 
                $data_final   = DateTime::createFromFormat('d/m/Y', $data_final);

                if( $data_final->diff($data_inicial)->m > 6 ){
                    throw new FieldValidation(traduz("A diferença de datas não pode ser maior que 6 meses"));
                }
            }

        }

        // Verifica se foi passado o numero da os e realiza a pesquisa pelo numero
        if( $sua_os ){

            $sql = mountSqlWithConditions([
                "AND (tbl_os.os_numero = '".str_replace("-", "", $sua_os)."' OR tbl_os.sua_os LIKE '{$sua_os}%' OR tbl_os.os = '".str_replace("-", "", $sua_os)."')"
            ], $joinsArray, $propertiesFields, $distinct);

            $pg_res = pg_query($con, $sql);
            $resultadoPesquisa = pg_fetch_all($pg_res);

            // Verifica a data da OS caso o usuario tenha tentado consultar uma OS antiga atraves do numero da OS
            if( $resultadoPesquisa ){
                if( !validateOsDate($resultadoPesquisa[0]['abertura']) ){
                    $resultadoPesquisa = [];
                    throw new FieldValidation(traduz("Os não habilitada para esta consulta"));
                }
            }
            
            if( $gerar_excel ){
                $caminho_download = mountCsv($resultadoPesquisa, null, $listaDeStatusDaOS, $cons_lista_de_legendas);
            }

        // Verifica pelo numero de serie
        }else if( $serie ){
            $sql = mountSqlWithConditions([
                "AND tbl_os.serie = '{$serie}'"
            ], $joinsArray, $propertiesFields, $distinct);

            $pg_res = pg_query($con, $sql);
            $resultadoPesquisa = pg_fetch_all($pg_res);

            if( $resultadoPesquisa ){
                if( !validateOsDate($resultadoPesquisa[0]['abertura']) ){
                    $resultadoPesquisa = [];
                    throw new FieldValidation(traduz("Os não habilitada para esta consulta"));
                }
            }

            if( $gerar_excel ){
                $caminho_download = mountCsv($resultadoPesquisa, null, $listaDeStatusDaOS, $cons_lista_de_legendas);
            }

            //echo nl2br($sql);
        // Verifica pelo CPF ou CNPJ
        }else if( ($data_inicial AND $data_final) OR !empty($os_aberta) ){
            $conditionsArray = [];

	    if($data_inicial AND $data_final){
		    if( $data_tipo ){
			if( $data_tipo == 'abertura' ){
			    $dateType = 'data_abertura';
			    $data_i = $data_inicial->format('Y-m-d');
			    $data_f = $data_final->format('Y-m-d');
			} elseif( $data_tipo == 'digitacao' ){
			    $dateType = 'data_digitacao';
			    $data_i = $data_inicial->format('Y-m-d 00:00:00');
			    $data_f = $data_final->format('Y-m-d 23:59:59');
			}
		    }/*else{
			$dateType = 'data_abertura';
			$data_i = $data_inicial->format('Y-m-d');
			$data_f = $data_final->format('Y-m-d');
		    }*/
	    }
            if(strlen($data_i)>0 && strlen($data_f)>0 &&  $dateType AND empty($os_aberta)){
                $conditionsArray[] = "AND tbl_os.{$dateType} >= '{$data_i}' AND tbl_os.{$dateType} <= '{$data_f}'";
            }

            if( $nome_consumidor ){
                //if( !$codigo_posto AND !$referencia_produto ){
                //    throw new FieldValidation(traduz("Especifique o Posto ou o Produto"));
                //}
            }

            if( $consumidor_cpf ){
                $conditionsArray[] = "AND tbl_os.consumidor_cpf = '{$consumidor_cpf}'";
            }

            if( $tipo_atendimento ){
                $conditionsArray[] = "AND tbl_tipo_atendimento.tipo_atendimento = {$tipo_atendimento}";
            }

            if( $statusOrcamento ){
                $conditionsArray[] = "AND tbl_os.status_os_ultimo = {$statusOrcamento}";
            }

            if( $tipo_os ){
                $conditionsArray[] = "AND consumidor_revenda = '{$tipo_os}'";
            }

            if( $nf_compra ){
                $conditionsArray[] = "AND tbl_os.nota_fiscal = '{$nf_compra}'";
            }
            
            if( strlen($status_checkpoint) > 0 ){
                $conditionsArray[] = "AND tbl_os.status_checkpoint = {$status_checkpoint}";
            }

            if( $estado ){
                $conditionsArray[] = "AND tbl_os.consumidor_estado IN ('{$estado}')";
            }

            if( $os_situacao ){
                $joinsArray[] = "JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato and tbl_extrato.fabrica = {$login_fabrica}";

                if( $os_situacao == 'APROVADA' ){
                    $conditionsArray[] = "AND tbl_extrato.aprovado IS NOT NULL";
                }else if( $os_situacao == 'PAGA'){
                    $joinsArray[] = "JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato";
                    $conditionsArray[] = "AND tbl_extrato_financeiro.data_envio IS NOT NULL"; 
                }
            }

            if( $linha ){
                $conditionsArray[] = "AND tbl_linha.linha = {$linha}";
                $joinsArray[] = "JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha and tbl_linha.fabrica = {$login_fabrica}";
            }

            if( $familia ){
                $conditionsArray[] = "AND tbl_familia.familia = {$familia}";
                $joinsArray[] = "JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia and tbl_familia.fabrica = {$login_fabrica}";
            }

            if( $os_troca ){
                $joinsArray[] = "JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os";
            }

            if( $os_aberta ){

		if(strlen($data_i)>0 && strlen($data_f)>0 &&  $dateType ){
	        	$conditionsArray[] = "AND tbl_os.{$dateType} >= '{$data_i}' AND tbl_os.{$dateType} <= '{$data_f}'";
		}

                $conditionsArray[] = "AND tbl_os.os_fechada IS FALSE ";
            }

            if( $codigo_posto ){
                $pg_res   = pg_query($con, "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '{$codigo_posto}'");
                $posto_id = pg_fetch_assoc($pg_res, 0);
                $conditionsArray[] = "AND tbl_os.posto in ({$posto_id['posto']})";
            }

            if( $referencia_produto ){
                $pg_res     = pg_query($con, "SELECT produto FROM tbl_produto WHERE referencia = '{$referencia_produto}'");
                $produto_id = pg_fetch_assoc($pg_res, 0);
                $conditionsArray[] = "AND tbl_produto.referencia = '{$referencia_produto}' AND tbl_produto.produto = '{$produto_id['produto']}'";
            }

            if( $nome_consumidor ){
                $conditionsArray[] = "AND upper(tbl_os.consumidor_nome) = upper('{$nome_consumidor}')";
            }

            if( $revenda_cnpj ){
                $conditionsArray[] = "AND tbl_os.revenda_cnpj LIKE '{$revenda_cnpj}%'";
            }
            

            if (in_array($login_fabrica, [186])) {
                $propertiesFields[] = "tbl_revenda.nome AS revenda_nome2";
                $joinsArray[]       = "LEFT JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda";
            }

            $sql = mountSqlWithConditions($conditionsArray, $joinsArray, $propertiesFields, $distinct);

            // Executa o sql principal da consulta
            $pg_res = pg_query($con, $sql);
            $resultadoPesquisa = pg_fetch_all($pg_res);

            if( $gerar_excel ){
                $caminho_download = mountCsv($resultadoPesquisa, null, $listaDeStatusDaOS, $cons_lista_de_legendas);
            }


        }else{
            throw new FieldValidation(traduz("Informe a data inicial e final para pesquisa")); 
        }
    }catch(FieldValidation $e){
        $msg = $e->getMessage();
    }
}

if( $_REQUEST['action'] == 'formulario_pre_os'){
   $pre_os = filter_input(INPUT_POST, 'pre_os');

    if (!empty($pre_os)) {
	   $pre_os_sql = "AND tbl_hd_chamado.hd_chamado = {$pre_os}";
    }

    if (in_array($login_fabrica, [186,195])) {    
        $campoPre = "tbl_hd_chamado_item.hd_chamado_item,tbl_hd_chamado_item.qtde,";
        $joinPre  = "
	     JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado and tbl_hd_chamado_item.produto is not null 
            JOIN tbl_produto ON tbl_hd_chamado_item.produto = tbl_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
        ";
        $whereOs = "AND tbl_hd_chamado_item.os IS NULL";
    } else {
	$joinPre = "LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}";
        $whereOS = "AND tbl_os.os IS NULL";
    } 

    if (!$areaAdmin) {
        $condPosto = "AND tbl_hd_chamado_extra.posto = {$login_posto}";
    }

	$sql = "SELECT
		DISTINCT
    tbl_hd_chamado.hd_chamado, 
    tbl_hd_chamado.cliente_admin,
    '' as sua_os, 
    tbl_hd_chamado_extra.serie, 
    tbl_hd_chamado_extra.nota_fiscal,
    TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
    TO_CHAR(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') AS data_nf,
    tbl_hd_chamado_extra.posto,
    tbl_posto_fabrica.codigo_posto,
    tbl_posto_fabrica.credenciamento,
    tbl_posto.nome AS posto_nome,
    tbl_hd_chamado_extra.fone as consumidor_fone,
    tbl_hd_chamado_extra.nome,
    tbl_hd_chamado_extra.array_campos_adicionais,
    tbl_marca.nome as marca_nome,
    {$campoPre}
    tbl_produto.referencia as produto_referencia,
    tbl_produto.descricao as produto_descricao
    FROM tbl_hd_chamado_extra
    JOIN tbl_hd_chamado using(hd_chamado)
    {$joinPre}
    LEFT JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca
    LEFT JOIN tbl_posto ON  tbl_posto.posto = tbl_hd_chamado_extra.posto
    LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
    LEFT JOIN tbl_os ON tbl_os.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_os.fabrica = {$login_fabrica}
    WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
    {$condPosto}
    AND tbl_hd_chamado_extra.abre_os = 't'
    {$pre_os_sql}
    {$whereOS}
    ORDER BY tbl_hd_chamado.hd_chamado DESC";

    $pg_res = pg_query($con, $sql);
    $resultadoPesquisaPreOs = pg_fetch_all($pg_res);

    if( $gerar_excel ){
        $caminho_download = mountCsv($resultadoPesquisaPreOs, $defaultFieldsPreOSCsv);
    }
}

$layout_menu = ($areaAdmin) ? 'callcenter' : 'os';
$title = traduz("SELEÇÃO DE PARAMÊTROS PARA RELAÇÃO DE ORDENS DE SERVIÇOS LANÇADAS");

if ($areaAdmin === true) {
    include __DIR__.'/admin/cabecalho_new.php';
} else {
    include __DIR__.'/cabecalho_new.php';
}

$plugins = array(
    "jquery",
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "multiselect",
    "select2",
    "dataTable",
    "alphanumeric"
);
include __DIR__.'/admin/plugin_loader.php';

?>
<style>
    .legenda_status_os{list-style: none;margin-left: 0px;}
    .legenda_status_os li{ display: inline-block; margin: 5px;width: 260px;}
    .text_legenda{display: inline-block;margin-top: -10px !important;vertical-align: text-bottom;font-weight: bold;}
    .legenda_sit_os{list-style: none;margin-left: 0px;}
    .legenda_sit_os li{ display: inline-block; margin: 5px;width: 405px;}
    .text_os_legenda{display: inline-block;margin-top: -10px !important;vertical-align: text-bottom;font-weight: bold;}
    
    .dropdown-menu {
        left: -95px !important;
    }

    .container-box{ list-style: none; }

    .container-box li{ display: flex; margin: 5px;}

    .box-legendas-os{ width: 23px; height: 17px; background-color: black; border: 1px solid black; margin-right: 5px;display: inline-block;}
    .box-subtitles-os{ width: 60px }
    .box-sm-os { width: 14px; height: 14px; }
    .btn_pesquisa_por_status{
        cursor:pointer;
    }
    .btn_pesquisa_por_status .text_legenda:hover{
        cursor:pointer;
        text-decoration: underline;
    }
</style>

<!-- Mensagens de erro -->
<?php if( !empty( $msg ) ) { ?>
<div class="alert alert-danger">
    <h4 style="text-transform: uppercase;"><?= $msg ?></h4>
</div>
<?php } ?>

<!-- Nenhuma OS encontrada -->
<?php if( (isset($resultadoPesquisa) AND empty($resultadoPesquisa)) OR  (isset($resultadoPesquisaPreOs) AND empty($resultadoPesquisaPreOs)) ) { ?>
<div class="alert alert-danger">
    <h4 style="text-transform: uppercase;">
        <?php echo traduz("Nenhuma OS encontrada");?>
    </h4>
</div>
<?php } ?>
<div class="container tc_container">
    <?php if ($areaAdmin  === false) {?>
        <div class="alert alert-info"> <?php echo traduz("Este relatório considera a data de digitação da OS");?></div>
    <?php  }?>
    <div class="tc_formulario">
        <form class="form-search form-inline" name="formulario_pesquisa" id="formulario_pesquisa" method="POST" action="">
            <input type="hidden" name="filtro_aberto" value="<?php echo ($filtro_aberto == 1) ? $filtro_aberto : 0;?>">
            <div class="titulo_tabela"> <?php echo traduz("Parâmetros de Pesquisa");?></div>
            <br>
            <?php if ($areaAdmin  === true) {?>
            <div class="row-fluid">
                <div class="span1"></div>
                <div class="span2" style="padding-top: 21px;">
                    <label class="radio">
                        <input type="radio" name="data_tipo" value="abertura" maxlength="10" <?php echo empty($data_tipo) || $data_tipo == 'abertura' ? 'checked' : '';?>> <?php echo traduz("Data Abertura");?>
                    </label>
                </div>
                <div class="span2" style="padding-top: 21px;">
                    <label class="radio">
                        <input type="radio" name="data_tipo" value="digitacao" maxlength="10" <?php echo !empty($data_tipo) AND $data_tipo == 'digitacao' ? 'checked' : '';?>> <?php echo traduz("Data Digitação");?>
                    </label>
                </div>
            </div>
            <?php  } else {?>
                <input type="hidden" name="data_tipo" value="abertura">
            <?php  }?>
            <div class="row-fluid">
                <div class="span1"></div>
                <div class="span2"> 
                    <label for="data_inicial"> <?php echo traduz("Data Inicial");?></label>
                    <input type="text" name="data_inicial" id="data_inicial" maxlength="10" class="input-block-level" autocomplete="off" onclick="if(this.value == 'dd/mm/aaaa') this.value = ''" value="<?= (!empty($data_inicial) and is_object($data_inicial)) ? $data_inicial->format('d/m/Y') : '';?>" />
                </div>
                <div class="span2">
                    <label for="data_final"> <?php echo traduz("Data Final");?></label>
                    <input type="text" name="data_final" id="data_final" maxlength="10" class="input-block-level" autocomplete="off" onclick="if(this.value == 'dd/mm/aaaa') this.value = ''" value="<?= (!empty($data_final) and is_object($data_final)) ? $data_final->format('d/m/Y') : '';?>"/>
                </div>
                <div class="span3" style="padding-top: 23px;">
                    <label class="checkbox">
                        <input type="checkbox" name="os_aberta" <?php echo !empty($os_aberta) ? 'checked' : '';?>>  <?php echo traduz("Apenas OS em aberto");?>
                    </label>
                </div>
                <div class="span2" style="padding-top: 23px;">
                    <label class="checkbox">
                        <input name="os_troca" type="checkbox" <?php echo !empty($os_troca) ? 'checked' : '';?>>  <?php echo traduz("Apenas OS Troca");?>
                    </label>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span1"></div>
                <div class="span2">
                    <label for="sua_os"><?php echo traduz("Número da OS");?></label>
                    <input type="text" maxlength="20" id="sua_os" name="sua_os" class="input-block-level" value='<?= !empty($sua_os) ? $sua_os : ""; ?>'>    
                </div>
                <div class="span2">
                    <label for="serie"><?php echo traduz("Número de Série");?></label>
                    <input type="text" maxlength="20" id="serie" name="serie" class="input-block-level" value="<?= !empty($serie) ? $serie : ""; ?>">
                </div>
                <div class="span2">
                    <label for="nf_compra"><?php echo traduz("NF. Compra");?></label>
                    <input type="text" maxlength="20" id="nf_compra" name="nf_compra" class="input-block-level" value="<?= !empty($nf_compra) ? $nf_compra : ""; ?>">
                </div>
                <div class="span4">
                    <label for="tipo_os"><?php echo traduz("Tipo de OS");?></label>
                    <select id="tipo_os" name="tipo_os" class="input-block-level">
                        <option value=""> Todas </option>
                        <option value="C" <?= !empty($tipo_os) == 'C' ? 'selected' : "" ?> > <?php echo traduz("Consumidor");?> </option>
                        <option value="R" <?= !empty($tipo_os) == 'R' ? 'selected' : "" ?> > <?php echo traduz("Revenda");?> </option>
                    </select>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span1"></div>
                <?php if ($login_fabrica != 139) { ?>
                    <div class="span5">
                        <label for="tipo_atendimento"> <?php echo traduz("Tipo de Atendimento");?></label>
                        <select name="tipo_atendimento" class="input-block-level">
                            <option value=""><?php echo traduz("Selecione...");?></option>
                            <?php foreach($listaDeTiposDeAtendimentos as $atendimento){ ?>
                                <option value="<?= $atendimento['tipo_atendimento'] ?>" <?= $tipo_atendimento AND $tipo_atendimento == $atendimento['tipo_atendimento'] ? 'selected' : "" ?>> <?= $atendimento['descricao'] ?> </option>
                            <?php } ?>
                        </select>    
                    </div>
                <?php } ?>
                <div class="span5">
                    <label for="status_checkpoint"> <?php echo traduz("Status da OS");?></label>
                    <select name="status_checkpoint" class="input-block-level">
                        <option value=""><?php echo traduz("Selecione...");?></option>
                        <?php foreach($listaDeStatusDaOS as $statusOS){ ?>
                            <option value="<?= $statusOS['status_checkpoint'] ?>" <?= $status_checkpoint AND $status_checkpoint == $statusOS['status_checkpoint'] ? 'selected' : "" ?>> <?= $statusOS['descricao'] ?> </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            
            <?php if ($login_fabrica == 191 AND $posto_interno) {
                    $status_orcamento = array(
                                            "255" => "Aguardando Orçamento",
                                            "75"  => "Aguardando Aprovação",
                                            "249" => "Aguardando Pagamento",
                                            "250" => "Aguardando Conserto",
                                            "251" => "Aguardando Retirada",
                                            "252" => "Pagamento Confirmado",
                                            "253" => "Finalizado",
                                            "241" => "Orçamento Reprovado",
                                            "189" => "Aguardando NF Retorno",
                                        );
            ?>
                    <div class="row-fluid">
                        <div class="span1"></div>
                        <div class="span5">
                            <label for="status_orcamento"> <?php echo traduz("Status do Orçamento");?></label>
                            <select name="status_orcamento" class="input-block-level">
                                <option value=""><?php echo traduz("Selecione...");?></option>
                                <?php foreach ($status_orcamento as $key => $value ) {

                                        if($statusOrcamento == $key ){
                                            $selectedTec = "SELECTED";
                                        }else{
                                            $selectedTec = "";
                                        }
                                ?>
                                        <option value="<?=$key;?>" <?=$selectedTec?> > <?=$value;?> </option>
                                <?php
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
            <?php } ?>

            <div style="max-height: 15px; height: 15px;"></div>
            <div id="div_busca_avancada" style="display: <?php echo ($filtro_aberto == 1) ? "" : "none";?>"> 
                <div class="row-fluid">
                    <div class="span1"></div>
                    <div class="span3">
                        <label for="consumidor_cpfconsumidor_cpf"> <?php echo traduz("CPF/CNPJ Consumidor");?></label>
                        <input type="text" maxlength="14" id="consumidor_cpf" name="consumidor_cpf" class="input-block-level" value="<?= !empty($consumidor_cpf) ? $consumidor_cpf : '';?>"> 
                    </div>
                    <div class="span7">
                        <label class="" for=""> <?php echo traduz("Nome do Consumidor");?></label>
                        <div style="display: flex;">
                            <input type="text" name="nome_consumidor" class="input-block-level" value="<?= !empty($nome_consumidor) ? $nome_consumidor : ""; ?>">
                        </div>
                    </div>
                </div>
                <?php if ($areaAdmin  === true) {?>
                <div class="row-fluid">
                    <div class="span1"></div>
                    <div class="span3">
                        <label for="linha"><?php echo traduz("Linha");?></label>
                        <select name="linha" class="input-block-level select2">
                            <option value=""><?php echo traduz("Selecione...");?></option>
                            <?php foreach($listaDeLinhas as $linha_) { ?>
                                <option value="<?= $linha_['linha'] ?>" <?php echo $linha AND $linha == $linha_['linha'] ? 'selected' : '';?>> <?= $linha_['nome'] ?> </option>
                            <?php } ?>
                        
                        </select>
                    </div>
                    <div class="span3">
                        <label for="familia"><?php echo traduz("Família");?></label>
                        <select name="familia" class="input-block-level select2">
                           <option value=""><?php echo traduz("Selecione...");?></option>

                           <?php foreach($listaDeFamilias as $familia_) { ?>
                                <option value="<?= $familia_['familia'] ?>" <?php echo $familia AND $familia == $familia_['familia'] ? 'selected' : '';?>> <?= $familia_['descricao'] ?> </option>
                           <?php } ?>
                        
                        </select>
                    </div>
                    <div class="span2" style="padding-top: 21px;">
                        <label class="radio">
                            <input type="radio" name="os_situacao" value="APROVADA" <?php !empty($os_situacao) AND $os_situacao == "APROVADA" ? 'checked' : '';?>> <?php echo traduz("OS's Aprovadas");?>
                        </label>
                    </div>
                    <div class="span2" style="padding-top: 21px;">
                        <label class="radio">
                            <input type="radio" name="os_situacao" value="PAGA" <?php !empty($os_situacao) AND $os_situacao == "PAGA" ? 'checked' : '';?>>  <?php echo traduz("OS's Pagas");?>
                        </label>
                    </div>
                </div>
                <div class="row-fluid">
                    <div class="span1"></div>
                    <div class="span3">
                        <div class="control-group" id="campo_cod_posto">
                            <label class="control-label" for=""><?php echo traduz("Código Posto");?></label>
                            <div class="controls controls-row">
                                <div class='span10 input-append'>
                                    <INPUT class='span12' TYPE="text" class="frm" NAME="codigo_posto" id="codigo_posto" value='<?= $_REQUEST['codigo_posto'] ?>'>
                                    <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                                    <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="span5">
                        <div class="control-group" id="campo_nome_posto">
                            <label class="control-label" for=""><?php echo traduz("Nome Posto");?></label>
                            <div class="controls controls-row">
                                <div class="span10 input-append">
                                    <INPUT class="span12" TYPE="text" class="frm" NAME="nome_posto" id="descricao_posto" value="<?= $_REQUEST['nome_posto'] ?>">
                                    <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                                    <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="span2">
                        <label class="" for=""><?php echo traduz("Estado");?></label>
                        <div style="display: flex;">
                            <select type="text" name="estado" class="input-block-level">
                                <option value=""><?php echo traduz("Selecione...");?></option>
                                <?php foreach($listaDeEstadosDoBrasil as $sigla => $estado_) { ?>
                                    <option value="<?= $sigla ?>" <?php $estado AND ($estado == $sigla) ? 'selected' : '';?>> <?= $estado_ ?> </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php }?>
                <div class="row-fluid">
                    <div class="span1"></div>
                    <div class="span3">
                        <div class="control-group">
                            <label class="control-label" for=""><?php echo traduz("Ref. Produto");?></label>
                            <div class="controls controls-row">
                                <div class="span10 input-append">
                                    <INPUT class='span12' TYPE="text" class="frm" name="produto_referencia" id="produto_referencia" value="<?= $_POST['produto_referencia'] ?>">
                                    <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                                    <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="span7">
                        <div class="control-group">
                        <label class="control-label" for=""><?php echo traduz("Descrição Produto");?></label>
                            <div class="controls controls-row">
                                <div class="span11 input-append ">
                                    <INPUT TYPE="text"class='span12' class="frm" value="<?= $_POST['produto_nome'] ?>" name="produto_nome" id="produto_descricao">
                                    <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                                    <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="titulo_coluna" style="text-align: center;font-size: 15px;font-weight: bold;padding-bottom: 10px;margin-bottom: 20px;">
                     <?php echo traduz("Consultar Pré-Ordem de Serviço");?>
                </div>
                <div class="row-fluid">
                    <div class="span3"></div>
                    <div class="span4">
                        <label for="pre_os"> <?php echo traduz("Número do Atendimento");?></label>
                        <input type="text" name="pre_os" class="input-block-level" value="<?= !empty($pre_os) ? $pre_os : ''; ?>" />
                    </div>
                    <div class="span3" style="padding-top: 20px;">
                        <button type="button" class="btn" onclick="changeAction(event, 'formulario_pre_os')"> Pesquisar Pré-OS </button>
                    </div>
                    <div class="span3"></div>
                </div>
                <hr >
                <div class="row-fluid">
                    <div class="span4"></div>
                    <div class="span4">
                        <label for="revenda_cnpj"><?php echo traduz("OS em aberto da Revenda = CNPJ");?></label>
                        <div style="display: flex;">

                            <?php if( !empty($revenda_cnpj)  ){ ?>
                                <input type="text" name="revenda_cnpj" class="input-block-level" maxlength="8" value="<?= $revenda_cnpj ?>" /> 
                            <?php } else { ?>
                                <input type="text" name="revenda_cnpj" class="input-block-level" maxlength="8" /> 
                            <?php } ?>

                            <div style="width: 70px; margin-top: 5px; margin-left: 3px">/0000-00</div> 
                        </div>
                    </div>
                    <div class="span4"></div>
                </div>
                <?php if ($areaAdmin  === true) {?>
                <div class="row-fluid" style="display: flex; justify-content: space-between; align-items: center">
                    <div class="span3"></div>
                    <div class="span4">
                        <label name="lbl_gerar_excel">
                            <input type="checkbox" name="gerar_excel"> <?php echo traduz("Gerar Excel (CSV)");?>
                        </label>
                    </div>
                    <div class="span3"></div>
                </div>
                <?php }?><br />
            </div><!-- FECHA DIV BUSCA AVANCADA -->
            <div class="row-fluid">
                <div class="span1"></div>
                <div class="span10" style="text-align: center">
                    <input type="hidden" name="action" value="formulario_pesquisa">
                    <button class="btn" type="button" onclick="$('#formulario_pesquisa').submit()">  <?php echo traduz("Pesquisar");?></button><br><br>
                    <div style="text-align: right;">
                    <button type="button" class="btn btn-small btn-mais-filtros btn-info"> <?php echo ($filtro_aberto == 1) ? traduz("Menos filtros") : traduz("Mais filtros");?> <i class="icon-list icon-white"></i></button><br><br>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div> 
<?php if( !empty($resultadoPesquisa) AND empty($resultadoPesquisaPreOs) ) { ?>
    <div class="row-fluid">
        <div class="span12">
            <h5 style="color: #63798D;font-size: 13px;">
                <?php echo traduz("Status das OS's");?>
            </h5>
            <ul class="legenda_status_os">
                <?php foreach( $listaDeStatusDaOS as $status ) {?>
                    <li class="btn_pesquisa_por_status" data-id="<?= $status['status_checkpoint'] ?> ">
                        <div class="box-legendas-os" style="background-color: <?= $status['cor'] ?>"></div>  
                        <div class="text_legenda">
                            <?= $status['descricao'] ?> 
                        </div> 
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div><hr />
    <div class="row-fluid">
        <div class="span12">
            <h5 style="color: #63798D;font-size: 13px;">
                <?php echo traduz("Legenda das OS's");?>
            </h5>
            <ul class="legenda_sit_os">
                <?php foreach( $cons_lista_de_legendas as $legenda) { ?>
                    <li> 
                        <div class="box-legendas-os box-subtitles-os" style="background-color: <?= $legenda['cor'] ?>"></div>  
                        <div class="text_os_legenda"> <?= $legenda['descricao'] ?></div>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
<?php } ?>
</div>
 <!-- Download arquivo CSV -->
<?php if( !empty($caminho_download) ){ ?> 
    <hr>
<div class="row-fluid">
    <div class="span4"></div>
    <div class="span4" style="text-align: center; padding: 5px; background-color: #d9e2ef; font-weight: bold">
        <a href="<?= $caminho_download ?>" target="_blank" style="text-decoration: none; ">
            <img src="imagens/icon_csv.png" height="40px" width="40px" align="absmiddle">&nbsp;&nbsp;&nbsp;
            <span class=""><?php echo traduz("Baixar Arquivo CSV");?></span>
        </a>
    </div>
    <div class="span4"></div>
</div>
<?php } ?>


<?php if( !empty($resultadoPesquisa) ) { ?>
    <div class="container-fluid">
        <table class="table table-bordered table-hover table-fixed" id="tabelas">
            <thead>
                <tr class="titulo_coluna">
                    <?php if ($areaAdmin  === false) {?>
                    <th nowrap><input type="checkbox" class="selecionaTodos"></th>
                    <?php }?>
                    <th nowrap><?php echo traduz("OS");?></th>
                    <?php if ($login_fabrica  == 190) {?>
                    <th nowrap><?php echo traduz("Contrato");?></th>
                    <?php }?>
                    <th nowrap><?php echo traduz("Série");?></th>
                    <th nowrap><?php echo traduz("AB");?></th>
                    <?php if ($login_fabrica == 139) { ?>
                        <th nowrap><?php echo traduz("DC");?></th>
                    <?php } ?>
                    <th nowrap><?php echo traduz("FC");?></th>
                    <?php if ($login_fabrica != 139) { ?>
                        <th nowrap><?php echo traduz("Tipo de Atendimento");?></th>
                    <?php } ?>
                    <th nowrap><?php echo traduz("C/R");?></th>
                    <?php if ($areaAdmin  === true) {?>
                    <th nowrap><?php echo traduz("Nome Posto");?></th>
                    <?php }?>
                    <th nowrap><?php echo traduz("Consumidor/Revenda");?></th>
                    <?php if ($login_fabrica != 139 || ($login_fabrica == 139 && $areaAdmin === true)) { ?>
                    <th nowrap><?php echo traduz("Cidade");?></th>
                    <th nowrap><?php echo traduz("Estado");?></th>
                    <?php } ?>
                    <th nowrap><?php echo traduz("Produto");?></th>
                    <th nowrap><?php echo traduz("NF");?></th>
                    <?php if ($login_fabrica == 191) {?>
                    <th nowrap><?php echo traduz("Status Orçamento");?></th>
                    <?php }?>
                    <?php if ($login_fabrica == 190) {?>
                    <?php if ($usaLaudoTecnicoOs && $areaAdmin  === true) {?>
                    <th nowrap><?php echo traduz("Visualizar Laudo Técnico");?></th>
                    <?php } else {?>
                    <th nowrap><?php echo traduz("Preencher Laudo Técnico");?></th>
                    <?php }?>
                    <?php }?>
                    <th nowrap><?php echo "<i class='icon-print icon-white'></i>";?></th>
                    <th nowrap><?php echo traduz("Ação");?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 0;
                foreach( $resultadoPesquisa as $row ) {

                    $NOME_CONSUMIDOR_REVENDA = '';
                    
                    switch ($row['consumidor_revenda']) {
                        case 'C':
                        case 'E':
                            $tipoConsumidor = traduz("Consumidor");
							$NOME_CONSUMIDOR_REVENDA = $row['consumidor_nome'];
							$numero_sua_os = $row['os'];
                            break;
                        case 'R':
                            $tipoConsumidor = traduz("Revenda");
							$NOME_CONSUMIDOR_REVENDA = $row['revenda_nome'];
							$numero_sua_os = $row['sua_os'];
                            break;
                    }

					if (in_array($login_fabrica, [186])) {
						$NOME_CONSUMIDOR_REVENDA = (strlen($row['revenda_nome2']) > 0 && $row['consumidor_revenda'] == 'R') ? $row['revenda_nome2'] : $NOME_CONSUMIDOR_REVENDA;
					}    

                    $impressa = ($row['impressa']) ? "<button title='".traduz("Impresso")."' type='button' class='btn btn-success btn-mini disabled'><i class='icon-ok icon-white'></i></button>" : "<button type='button' title='".traduz("imprimir.os")."' class='btn btn-mini disabled'><i class='icon-print'></i></button>";

                ?>
                    <tr class="tr_os_<?php echo $i;?>"  style="background-color: <?= verifySubtitles($row, $cons_lista_de_legendas)['cor'];?> !important">
                        <?php if ($areaAdmin  === false) {?>
                            <td  class="tac" nowrap><input type="checkbox"  value="<?= $row['os'] ?>" name="imprimir_os[]" id="impressao_<?= $row['os'] ?>" class="imprimir"></td>
                        <?php }?>
                        <td nowrap width="105"> 
                            <?php foreach( $listaDeStatusDaOS as $status ) { ?>
                                <?php if( $status['status_checkpoint'] == $row['status_checkpoint'] ) { ?>
                                    <div class="box-legendas-os box-sm-os" style="background-color: <?= $status['cor'] ?>"></div> 
                                    <a href="os_press.php?os=<?= $row['os'] ?>" target='_blank'> <?= $row['sua_os'] ?> </a>
                                <?php } ?>
                            <?php } ?> 
                        </td>
                        <?php if ($login_fabrica  == 190) {?>
                        <td class="tac"> 
                            <?php if ($areaAdmin  === true) {?>
    				            <a href="print_contrato.php?tipo=contrato&contrato=<?= $row['contrato_id'] ?>" target='_blank'><?= $row['contrato_id'] ?></a> 
                            <?php } else {?>
                                <?= $row['contrato_id'] ?>
                            <?php } ?>
			            </td>
                        <?php }?>
                        <td class="tac"> <?= $row['serie'] ?> </td>
                        <td class="tac"> <?= $row['abertura'] ?> </td>
                        <?php if ($login_fabrica == 139) { ?>
                            <td class="tac"> <?= $row['data_conserto'] ?> </td>
                        <?php } ?>
                        <td class="tac"> <?= $row['fechamento'] ?> </td>
                        <?php if ($login_fabrica != 139) { ?>
                            <td> <?= $row['descricao'] ?> </td>
                        <?php  } ?>
                        <td> <?= $tipoConsumidor;?></td>
                        <?php if ($areaAdmin  === true) {?>
                        <td> <?= $row['posto_nome'] ?> </td>
                        <?php }?>
                        <td> <?= $NOME_CONSUMIDOR_REVENDA ?> </td>
                        <?php if ($login_fabrica != 139 || ($login_fabrica == 139 && $areaAdmin === true)) { ?>
                            <td> <?= $row['contato_cidade'] ?> </td>
                            <td class="tac"> <?= $row['contato_estado'] ?> </td>
                        <?php } ?>
                        <td> <?= $row['produto_descricao'] ?> </td>
                        <td class="tac"> <?= $row['nota_fiscal'] ?> </td>
                        <?php if ($login_fabrica == 191) {?>
                                    <td class="tac"> <?= $row['status_orcamento'] ?> </td>
                        <?php }?>

                        <?php 
                        if ($login_fabrica == 190) {
			    $jaRespondido = false;
                            $sqlLaudoTecnico = "SELECT os FROM tbl_laudo_tecnico_os WHERE fabrica = {$login_fabrica} AND os = ".$row['sua_os'];
                            $resLaudoTecnico = pg_query($con, $sqlLaudoTecnico);

                            if (pg_num_rows($resLaudoTecnico) > 0) {
                                $jaRespondido = true;
                            }
                            if ($usaLaudoTecnicoOs && $areaAdmin  === true) { 
                                if ($jaRespondido) {
                                    echo "<td class='tac'><button type='button' class='btn btn-info btn-small btn-visualizar-laudo-tecnico' data-os='".$row['sua_os']."'>Visualizar</button></td>";
                                } else {
                                    echo "<td class='tac'></td>";
                                }                         
                            } else {
                                if ($jaRespondido) {
                                    echo "<td class='tac'><button type='button' class='btn btn-info btn-small btn-visualizar-laudo-tecnico' data-os='".$row['sua_os']."'>Visualizar</button></td>";
                                } else {
                                    echo "<td class='tac'><button type='button' onclick='preencherLaudoTecnico(".$row['sua_os'].",".$i.")' class='btn btn-info btn-small' data-os='".$row['sua_os']."'>Preencher</button></td>";
                                }
                            }
			}
                        ?>


                        <td class="tac"> <?= $impressa ?> </td>
                        <td nowrap> 
                            <div class="btn-group btn-block">
                              <button class="btn btn-smal"><i class="icon-list"></i></button>
                              <button class="btn btn-smal dropdown-toggle" data-toggle="dropdown">
                                <span class="caret"></span>
                              </button>
                              <ul class="dropdown-menu">
                                <?php 
                                        /* Implantação Dancor, foi solicitado a condição  "&& ($row["status_checkpoint"] <> 9 && isset($novaTelaOs)"
                                         * para TODAS FÁBRICAS que usa a nova tela de OS.
                                        */
                                ?>
                                <?php if ($areaAdmin === true && ($row["status_checkpoint"] <> 9 && isset($novaTelaOs))) {?>
                                    <li><a href="os_troca_subconjunto.php?os=<?= $row['os'] ?>" title="<?php echo traduz("Trocar");?>" target="_blank"><i class="icon-refresh"></i> <?php echo traduz("Trocar");?></a></li>
                                    <li class="divider"></li>

                                <?php } else {?>
                                    <li><a href="os_print.php?os=<?= $row['os'] ?>" title="<?php echo traduz("Imprimir");?>" target="_blank"><i class="icon-print"></i> <?php echo traduz("Imprimir");?></a></li>
                                    <li class="divider"></li>
                                    <?php if($login_fabrica == 191 AND $posto_interno == "t" && ($row["status_checkpoint"] <> 9 && isset($novaTelaOs))){ ?>
                                            <li><a href="os_troca_subconjunto_posto_interno.php?os=<?= $row['os'] ?>" title="<?php echo traduz("Trocar");?>" target="_blank"><i class="icon-refresh"></i> <?php echo traduz("Trocar");?></a></li>
                                            <li class="divider"></li>
                                    <?php } ?>
                                <?php }?>
                                 <?php if (($row["status_checkpoint"] <> 9 && !in_array($login_fabrica, [139,191])) || ($row["status_checkpoint"] <> 9 && in_array($login_fabrica, [139,191]) && $areaAdmin !== true) ) {?>   
                                <li><a href="fechamento_os.php?sua_os=<?= $numero_sua_os ?>&btn_acao=submit" title="<?php echo traduz("Fechar");?>" target="_blank"><i class="icon-ok"></i> <?php echo traduz("Fechar");?></a></li>
                                <li class="divider"></li>
                                <?php }?>
                                <?php 
                                
                                    echo retorna_botao_excluir_os($con, $row['os'],  $reparoNaFabrica,$i,$numero_sua_os);

		    		                echo retorna_botao_consertado_os($con, $row['os'], $row['data_conserto'], $row['finalizada'],$i,$numero_sua_os);


            		    		    if(in_array($login_fabrica, [191,193]) AND $areaAdmin == true){
                					    echo retorna_botao_cancelar_reabrir_os($con, $row['os'], $row['extrato'], $row['finalizada'],$i,$numero_sua_os,$row['excluida']);
                				    }
                                    
                                ?>
                                <?php if ($row["status_checkpoint"] <> 9) {?>
                                <li><a href="cadastro_os.php?os_id=<?= $row['os'] ?>" title="<?php echo traduz("Lançar Itens");?>" target="_blank"><i class="icon-edit"></i> <?php echo traduz("Lançar Itens");?></a></li>

                                <li class="divider"></li>
                                <?php }?>
                                <li><a href="os_press.php?os=<?= $row['os'] ?>" title="<?php echo traduz("Consultar");?>" target="_blank"><i class="icon-search"></i> <?php echo traduz("Consultar");?></a></li>
                              </ul>
                            </div>
                        </td>
                    </tr>
                <?php $i++; } ?>
            </tbody>
        </table>
    </div>
    <?php if ($areaAdmin === false) {?>
        <div class="container-fluid">
            <div class="well" style="padding-bottom: 0px;">
                <form action="os_print_varios.php" id="imprimir_varios" name="imprimir_varios" target="_blank" method="post">
                    <div class="row-fluid" style="min-height: 0px !important;">
                        <div class="span2"></div>
                        <div class="span2" style="text-align: right;">
                            <p style="margin-top: 10px;font-weight: bold;"><?php echo traduz("Formato da Página");?></p>
                        </div>
                        <div class="span3">
                            <select name="formato_arquivo" class="span12" style="margin-top: 6px;">
                                <option value="jacto"><?php echo traduz("jato.de.tinta/laser");?></option>
                                <option value="matricial"><?php echo traduz("matricial");?></option>
                            </select>
                        </div>
                        <div class="span3">
                            <button type="button" style="margin-top: 6px;" id="imprimir_botao" class="btn btn-imprimir-selecionados imprimir_botao"><i class="icon-print"></i> <?php echo traduz("Imprimir Selecionados");?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php }?>
<?php } ?>

<?php if( !empty($resultadoPesquisaPreOs) ) { ?>
    <div class="container-fluid">
        <table class="table table-bordered table-hover table-fixed" id="tabelas">
            <thead>
                <tr class="titulo_coluna">
                    <th nowrap><?php echo traduz("Nº Atendimento");?></th>
                    <th nowrap><?php echo traduz("Série");?></th>
                    <th nowrap><?php echo traduz("AB");?></th>
                    <?php if (!in_array($login_fabrica,array(191))) {?>
                    <th nowrap><?php echo traduz("DF");?></th>
                    <?php }?>
                    <th nowrap><?php echo traduz("Nome Posto");?></th>
                    <th nowrap><?php echo traduz("Consumidor/Revenda");?></th>
                    <th nowrap><?php echo traduz("NF");?></th>
                    <?php if (in_array($login_fabrica,array(191))) {?>
                    <th nowrap><?php echo traduz("NF Remessa");?></th>
                    <?php }?>
                    <th nowrap><?php echo traduz("Produto");?></th>
                    <?php if ($areaAdmin  === false) {?>
                    <th nowrap><?php echo traduz("Ação");?></th>
                    <?php }?>
                </tr>
            </thead>
            <tbody>
                <?php 

                if ($login_fabrica == 186) {
                    foreach ($resultadoPesquisaPreOs as $key => $value) {
                        for ($i=1; $i <=$value["qtde"]  ; $i++) { 
                            $novoResultPreOS[] = $value;
                        }
                        
                    }

                    if (count($novoResultPreOS) > 0) {
                        $resultadoPesquisaPreOs = $novoResultPreOS;
                    }
                }


                foreach( $resultadoPesquisaPreOs as $row ) { 
                        $adicionais = json_decode($row['array_campos_adicionais'],true);
                ?>
                        <tr>
                            <td class="tac"> <?= ($login_fabrica == 191 AND !empty($row['cliente_admin'])) ? "REVENDA" : $row['hd_chamado'] ?> </td>
                            <td> <?= $row['serie'] ?> </td>
                            <td> <?= $row['data'] ?> </td>
                            <?php if (!in_array($login_fabrica,array(191))) {?>
                            <td> <?= $row['df'] ?> </td>
                            <?php }?>
                            <td> <?= $row['posto_nome'] ?> </td>
                            <td> <?= $row['nome'] ?> </td>
                            <td> <?= $row['nota_fiscal'] ?> </td>
                            <?php if (in_array($login_fabrica,array(191))) {?>
                            <td> <?= $adicionais['numero_nf_remessa'] ?> </td>
                            <?php }?>
                            <td> <?= $row['produto_referencia'] . ' - ' . $row['produto_descricao'] ?> </td>
                            <?php if ($areaAdmin  === false) {?>
                            <td class="tac" nowrap><a href="cadastro_os.php?preos=<?= $row['hd_chamado'];?>&hd_chamado_item=<?= $row['hd_chamado_item'];?>" class="btn btn-mini btn-primary" target="_blank"><?php echo traduz("Abrir Pré-OS");?></a>
                            <?php }
				if(in_array($login_fabrica,array(191))){
			    ?>
					<a href="javascript: void(0)" onclick="excluiPreOs($(this),<?=$row['hd_chamado']?>)" class="btn btn-mini btn-primary"><?php echo traduz("Excluir Pré-OS");?></a>
			    <?php
				}
			    ?>
				</td>
                        </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php } ?>


<script>
    $(function(){
        $('.select2').select2();
        $('#data_inicial').datepicker();
        $('#data_final').datepicker();
        $(".btn-mais-filtros").click(function(){
            if( $("#div_busca_avancada").is(":visible")){
              $("#div_busca_avancada").hide('slow');
              $("input[name=filtro_aberto]").val(0);
              $(".btn-mais-filtros").html(' <?php echo traduz("Mais filtros");?><i class="icon-list icon-white"></i>');

            }else{
                $("input[name=filtro_aberto]").val(1);
              $("#div_busca_avancada").show('slow');
              $(".btn-mais-filtros").html(' <?php echo traduz("Menos filtros");?><i class="icon-list icon-white"></i>');
            }
        });
        // configura o datatable
        $('#tabelas').DataTable({
            aaSorting: [[3, 'desc']],
            <?= (in_array($login_fabrica, [193])) ? '"iDisplayLength": 100,' : ''; ?>
            "oLanguage": {
                "sLengthMenu": "Mostrar <select>" +
                                '<option value="10"> 10 </option>' +
                                '<option value="50"> 50 </option>' +
                                '<option value="100"> 100 </option>' +
                                '<option value="150"> 150 </option>' +
                                '<option value="200"> 200 </option>' +
                                '<option value="-1">  <?php echo traduz("Tudo");?></option>' +
                                '</select> <?php echo traduz("resultados");?>',
                "sSearch": "Procurar:",
                "sInfo": "<?php echo traduz("Mostrando de");?> _START_ <?php echo traduz("até");?> _END_ <?php echo traduz("de um total de");?> _TOTAL_ <?php echo traduz("registros");?>",         
                "oPaginate": {
                    "sFirst": "<?php echo traduz("Primeira página");?>",
                    "sLast": "<?php echo traduz("Ãšltima página");?>",
                    "sNext": "<?php echo traduz("Próximo");?>",
                    "sPrevious": "<?php echo traduz("Anterior");?>"
                }
            }
        });

        // Mascaras
        $('#data_inicial').mask('00/00/0000');
        $('#data_final').mask('00/00/0000');

        // Inicia o ShadowBox
        Shadowbox.init();   
        $(".btn_pesquisa_por_status").click(function(){
            var status = $(this).data("id");
            $("input[name=filtro_aberto]").val(0);
            $("select[name=status_checkpoint] option[value="+status+"]").prop("selected", true);
            $("#formulario_pesquisa").submit();
        });
        $.autocompleteLoad(Array("produto", "posto"));

        $("span[rel=lupa]").click(function () {
            $.lupa($(this),[]);
        });
        
        $('.selecionaTodos').click(function(){
            if($(this).is(':checked')){
                $('.imprimir').attr('checked',true);
            }else{
                $('.imprimir').attr('checked',false);
            }
        });

        $('#imprimir_botao').click(function(){

            selecionado = false;

            $('#imprimir_varios input[type=hidden]').remove();

            $('.imprimir').each(function(){
                if($(this).is(':checked')){
                    selecionado = true;
                    $('#imprimir_varios').append('<input type="hidden" value="'+$(this).val()+'" name="imprime_os[]" checked="checked" />');
                }
            });

            if(selecionado){
                if(confirm('<?php echo traduz("deseja.mesmo.imprimir.todas.as.os.selecionadas");?>')){
                    $('#imprimir_varios').submit();
                }
            }else{
                alert('<?php echo traduz("selecione.ao.menos.uma.os");?>');
                return  false;
            }
        });

        $(document).on('click', '.btn-visualizar-laudo-tecnico', function() {
            let os = $(this).data('os');
           
            Shadowbox.open({
                content: '<div style=\'text-align: center; background-color: #FFF;\' ><h1><i class=\'fa fa-spinner fa-pulse\'></i> Gerando Laudo Técnico</h1></div>',
                player: 'html',
                height: 48,
                options: {
                    modal: true,
                    enableKeys: false,
                    displayNav: false
                }
            });
            
            setTimeout(function() {
                $.ajax({
                    url: window.location,
                    type: 'get',
                    data: {
                        ajax: 'busca_laudo_tecnico_os',
                        os: os,
                        readonly: true
                    },
                    async: true,
                    timeout: 60000
                }).fail(function(res) {
                    alert('Erro ao gerar laudo técnico');
                    Shadowbox.close();
                }).done(function(res, req){
                    if (req == 'success') {
                        res = JSON.parse(res);
                        
                        if (res.erro) {
                            alert(res.erro);
                            Shadowbox.close();
                        } else {
                            Shadowbox.close();
                            
                            setTimeout(function() {
                                Shadowbox.open({
                                    content: '<div id=\'sb-player\' ></div>',
                                    player: 'html',
                                    height: window.innerHeight,
                                    width: window.innerWidth,
                                    options: {
                                        modal: true,
                                        enableKeys: false,
                                        onFinish: function() {
                                            let player = $('#sb-player');
                                            let iframe = $('<iframe></iframe>', { 
                                                src: 'os_laudo_tecnico.php?os='+os+'&readonly=true', 
                                                css: {
                                                    height: '100%',
                                                    width: '100%'
                                                }
                                            });
                                            
                                            $(iframe).on('load', function(e) {
                                                e.target.contentWindow.postMessage('setFbData|'+res.titulo, '*');
                                                
                                                let data = {
                                                    edit: false,
                                                    title: 'Laudo Técnico - OS '+res.sua_os,
                                                    logo: $('#logo_fabrica').attr('src'),
                                                    formData: res.observacao,
                                                    noActions: true
                                                };
                                                
                                                e.target.contentWindow.postMessage('toggleFbEdit|'+JSON.stringify(data), '*');
                                            });
                                            
                                            $(player).html(iframe);
                                            $(player).css({ overflow: 'hidden' });
                                        }
                                    }
                                });
                            }, 1000);
                        }
                    } else {
                        alert('Erro ao gerar laudo técnico');
                        Shadowbox.close();
                    }
                });
            }, 1000);
        });
        

    })

    // Altera a aÃƒÂ§ÃƒÂ£o do formulario conforme o botÃƒÂ£o de aÃƒÂ§ÃƒÂ£o
    function changeAction(event, action){
        event.preventDefault();

        let form = $('#formulario_pesquisa');
        let input = $('input[name=action]');

        input.val(action);
        form.submit();
    }


    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

    function retorna_produto (retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_nome").val(retorno.descricao);
    }

    function consertadoOS(os ,indice,fabrica) {
        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:"consertado",
                os:os
            }
        })
        .done(function(data){
            if(data.erro == false){
                alert('<?php echo traduz("OS consertada com sucesso");?>');
                verificaGeraPedidoDevolucao(os);
            } else {
                alert('<?php echo traduz("Não foi possível realizar a operação.");?>');
            }
        })
        .fail(function(){
            alert('<?php echo traduz("Não foi possível realizar a operação.");?>');
        });
    }

    function excluirOs(os ,indice ) {
        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:"excluir",
                os:os
            }
        })
        .done(function(data){
            console.log(data)
            if(data.erro == false){
                alert('<?php echo traduz("OS excluida com sucesso");?>');
                $(".tr_os_"+indice).remove();
            } else {
                alert('<?php echo traduz("Não foi possível realizar a operação.");?>');
            }
        })
        .fail(function(){
            alert('<?php echo traduz("Não foi possível realizar a operação.");?>');
        });
    }

    function cancelar_os(os, btn, continuar) {

        if (typeof continuar == "undefined" || continuar == null) {
            continuar = false;
        }

        if (continuar || confirm("Deseja realmente cancelar a Ordem de Serviço ?")) {
            if (continuar) {
                var mensagem = continuar;
            } else {
                var mensagem = prompt("Informe o motivo do cancelamento");

                if (mensagem == null) {
                    return false;
                }

                mensagem = $.trim(mensagem);

                 if (mensagem.length == 0) {
                    alert("É necessário informar o motivo para cancelar a ordem de serviço");
                    return false;
                }
            }

            $.ajax({
               url: window.location,
               type: "post",
               data: { ajax_cancelar_os: true, os: os, mensagem: mensagem, continuar: continuar },
               timeout: 60000
            }).fail(function(res) {
                alert("Erro ao cancelar a ordem de serviço, tempo limite esgotado.");
            }).done(function(res) {
                res = JSON.parse(res);

                if (res.erro) {
                    alert(res.erro);
                } else if (res.continuar) {
                    if (confirm(res.continuar)) {
                        cancelar_os(os, btn, mensagem);
                    } 
                } else {
                    
                    alert("Ordem de Serviço cancelada com sucesso.");
                }
            });
        }
    }

    function reabrir_os(os ,btn ) {
        if (confirm("Deseja realmente reabrir a Ordem de Serviço ?")) {
            var mensagem = prompt("Informe o motivo para reabrir");

            if (mensagem == null) {
                return false;
            }

            mensagem = $.trim(mensagem);

            if (mensagem.length == 0) {
                alert("É necessário informar o motivo para reabrir a ordem de serviço");
                return false;
            }

            $.ajax({
               url: window.location,
               type: "post",
               data: { ajax_reabrir_os: true, os: os, mensagem: mensagem },
               timeout: 60000
            }).fail(function(res) {
                alert("Erro ao reabrir a ordem de serviço, tempo limite esgotado.");
            }).done(function(res) {
                res = JSON.parse(res);

                if (res.erro) {
                    alert(res.erro);
                } else {
                    alert("Ordem de Serviço reaberta com sucesso");
                }
            });
        }
    }

    function verificaGeraPedidoDevolucao(os) {

        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:"verificaGeraPedidoDevolucao",
                os:os
            }
        })
        .done(function(data){
           if(data.erro == false){
                if(confirm(data.msg)) {
                    geraPedidoDevolucao(os)
                } else {
                    return false;
                }
            } else {
                return false;
            }
        })
        .fail(function(data){
            return false;
        });
    }

    function geraPedidoDevolucao(os) {
        $.ajax({
            async: false,
            url : "os_cadastro_unico/fabricas/<?=$login_fabrica?>/ajax_gerar_pedido_manual.php",
            type: "get",
            data: { gera_pedido_manual: true, os: os},
            success: function(data) {
                data = JSON.parse(data);
                if (data.erro) {
                    alert(data.erro);
                    return false;
                } else {
                    alert("Pedido Gerado com Sucesso");
                    location.reload();
                }
            }
        });
    }

    function excluiPreOs(obj,hd_chamado){

	    $.ajax({
		url: "<?=$PHP_SELF?>",
		data:{
		    ajax: "excluir_pre_os", 
		    hd_chamado : hd_chamado 
		},
		type: 'POST',
		beforeSend: function () {
		    $("#loading_pre_os").show();
		},
		complete: function(data) {
		    data = $.parseJSON(data.responseText);

		    if(data.sucesso){
			    $(obj).parents('tr').hide();
			    alert("Pré-atendimento excluído com sucesso");
		    }
		    if(data.erro){
			    alert("Falha ao excluir o Pré-atendimento");
		    }

		    $("#loading_pre_os").hide();
		}
	    });
    }

    function preencherLaudoTecnico(os, indice) {
        Shadowbox.open({
            content: '<div style=\'text-align: center; background-color: #FFF;\' ><h1><i class=\'fa fa-spinner fa-pulse\'></i> Gerando Laudo Técnico</h1></div>',
            player: 'html',
            height: 48,
            options: {
                modal: true,
                enableKeys: false,
                displayNav: false
            }
        });
        
        setTimeout(function() {
            $.ajax({
                url: window.location,
                type: 'get',
                data: {
                    ajax: 'busca_laudo_tecnico',
                    os: os
                },
                async: true,
                timeout: 60000
            }).fail(function(res) {
                alert('Erro ao gerar laudo técnico');
                Shadowbox.close();
            }).done(function(res, req){
                if (req == 'success') {
                    res = JSON.parse(res);
                    
                    if (res.erro) {
                        alert(res.erro);
                        Shadowbox.close();
                    } else {
                        Shadowbox.close();
                        
                        setTimeout(function() {
                            Shadowbox.open({
                                content: '<div id=\'sb-player\' ></div>',
                                player: 'html',
                                height: window.innerHeight,
                                width: window.innerWidth,
                                options: {
                                    modal: true,
                                    enableKeys: false,
                                    onFinish: function() {
                                        let player = $('#sb-player');
                                        let iframe = $('<iframe></iframe>', { 
                                            src: 'os_laudo_tecnico.php?os='+os, 
                                            css: {
                                                height: '100%',
                                                width: '100%'
                                            }
                                        });
                                        
                                        $(iframe).on('load', function(e) {
                                            e.target.contentWindow.postMessage('setFbData|'+res.comentario, '*');
                                            
                                            let data = {
                                                edit: false,
                                                title: 'Laudo Técnico - OS '+res.sua_os,
                                                logo: $('#logo_fabrica').attr('src')
                                            };
                                            
                                            e.target.contentWindow.postMessage('toggleFbEdit|'+JSON.stringify(data), '*');
                                        });
                                        
                                        $(player).html(iframe);
                                        $(player).css({ overflow: 'hidden' });
                                    }
                                }
                            });
                        }, 1000);
                    }
                } else {
                    alert('Erro ao gerar laudo técnico');
                    Shadowbox.close();
                }
            });
        }, 1000);
    }
    
</script>
<? include "rodape.php"; ?>
