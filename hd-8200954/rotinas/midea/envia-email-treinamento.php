<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
                           
include dirname(__FILE__) . '/../../class/ComunicatorMirror.php';

try {

    $fabrica = 169;
    $msg_erro = array();
   
    /*
    * Cron Class
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    $comunicatorMirror = new ComunicatorMirror();

    $sql = "
        SELECT 
            tbl_posto.nome AS nome_posto,
            tbl_posto_fabrica.posto AS id_posto,
            tbl_posto_fabrica.contato_email AS posto_email,
            tbl_treinamento.treinamento,
            tbl_treinamento.titulo,
            tbl_treinamento.descricao,
            TO_CHAR(tbl_treinamento.data_inicio, 'DD/MM/YYYY HH24:MI:SS') AS data_inicio,
            TO_CHAR(tbl_treinamento.data_fim, 'DD/MM/YYYY HH24:MI:SS') AS data_fim,
            tbl_cidade.nome AS nome_cidade,
            tbl_cidade.estado,
            TO_CHAR(tbl_treinamento.prazo_inscricao, 'DD/MM/YYYY') AS prazo_inscricao,
            tbl_treinamento.local AS local_treinamento,
            tbl_treinamento.vagas,
            tbl_treinamento.qtde_participante
        FROM tbl_posto_fabrica
        JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
        JOIN tbl_treinamento ON tbl_treinamento.fabrica = $fabrica
        JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_treinamento.linha
        JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = $fabrica
        JOIN tbl_cidade ON tbl_cidade.cidade = tbl_treinamento.cidade
        JOIN tbl_treinamento_tipo ON tbl_treinamento.treinamento_tipo = tbl_treinamento_tipo.treinamento_tipo
        AND tbl_posto_fabrica.credenciamento != 'DESCREDENCIADO'
        AND tbl_posto_fabrica.tipo_posto = tbl_treinamento.tipo_posto
        AND tbl_posto_linha.linha = tbl_treinamento.linha
        AND lower(tbl_treinamento_tipo.nome) != lower('Palestra')
        AND tbl_treinamento.ativo IS TRUE
        AND tbl_treinamento.data_finalizado IS NULL
        AND tbl_treinamento.data_input::date = current_date - interval '1 day'
        AND (
                (
                    SELECT count(tbl_cidade.cidade)
                    FROM tbl_treinamento_cidade
                    JOIN tbl_cidade ON tbl_treinamento_cidade.cidade = tbl_cidade.cidade
                    WHERE tbl_treinamento_cidade.treinamento = tbl_treinamento.treinamento
                    AND tbl_cidade.cod_ibge = tbl_posto_fabrica.cod_ibge
                ) > 0
                OR (
                    SELECT count(tbl_treinamento_cidade.estado)
                    FROM tbl_treinamento_cidade
                    WHERE tbl_treinamento_cidade.treinamento = tbl_treinamento.treinamento
                    AND tbl_treinamento_cidade.estado = tbl_posto.estado
                ) > 0
                OR (
                    SELECT count(tbl_treinamento_posto.treinamento_posto)
                    FROM tbl_treinamento_posto
                    WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                    AND tbl_treinamento_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
                ) > 0
            )
        GROUP BY 
            tbl_posto.nome,
            tbl_posto_fabrica.contato_email,
            tbl_treinamento.treinamento,
            tbl_treinamento.titulo,
            tbl_treinamento.descricao,
            tbl_treinamento.data_inicio,
            tbl_treinamento.data_fim,
            tbl_cidade.nome,
            tbl_cidade.estado,
            tbl_treinamento.prazo_inscricao,
            tbl_treinamento.vagas,
            tbl_treinamento.qtde_participante,
            tbl_posto_fabrica.posto,
            tbl_treinamento.local";
    $res = pg_query($con, $sql);
    if (strlen(pg_last_error()) > 0) {
        $msg_erro["msg"][] =  "Erro ao pesquisar postos para envio de email treinamento";
    }
    if (pg_num_rows($res) > 0){
        $dados_treinamento = pg_fetch_all($res);
    }

    $sql2 = "
        SELECT 
            tbl_posto.nome AS nome_posto,
            tbl_posto_fabrica.posto AS id_posto,
            tbl_posto_fabrica.contato_email AS posto_email,
            tbl_treinamento.treinamento,
            tbl_treinamento.titulo,
            tbl_treinamento.descricao,
            TO_CHAR(tbl_treinamento.data_inicio, 'DD/MM/YYYY HH24:MI:SS') AS data_inicio,
            TO_CHAR(tbl_treinamento.data_fim, 'DD/MM/YYYY HH24:MI:SS') AS data_fim,
            tbl_cidade.nome AS nome_cidade,
            tbl_cidade.estado,
            TO_CHAR(tbl_treinamento.prazo_inscricao, 'DD/MM/YYYY') AS prazo_inscricao,
            tbl_treinamento.local AS local_treinamento,
            tbl_treinamento.vagas,
            tbl_treinamento.qtde_participante
        FROM tbl_posto_fabrica
        JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
        JOIN tbl_treinamento ON tbl_treinamento.fabrica = $fabrica
        JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_treinamento.linha
        JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = $fabrica
        JOIN tbl_cidade ON tbl_cidade.cidade = tbl_treinamento.cidade
        JOIN tbl_treinamento_tipo ON tbl_treinamento.treinamento_tipo = tbl_treinamento_tipo.treinamento_tipo
        AND tbl_posto_fabrica.credenciamento != 'DESCREDENCIADO'
        AND tbl_posto_fabrica.tipo_posto = tbl_treinamento.tipo_posto
        AND tbl_posto_linha.linha = tbl_treinamento.linha
        AND lower(tbl_treinamento_tipo.nome) != lower('Palestra')
        AND tbl_treinamento.ativo IS TRUE
        AND tbl_treinamento.data_finalizado IS NULL
        AND tbl_treinamento.data_inicio::date = current_date + interval '7 days'
        AND (
                (
                    SELECT count(tbl_cidade.cidade)
                    FROM tbl_treinamento_cidade
                    JOIN tbl_cidade ON tbl_treinamento_cidade.cidade = tbl_cidade.cidade
                    WHERE tbl_treinamento_cidade.treinamento = tbl_treinamento.treinamento
                    AND tbl_cidade.cod_ibge = tbl_posto_fabrica.cod_ibge
                ) > 0
                OR (
                    SELECT count(tbl_treinamento_cidade.estado)
                    FROM tbl_treinamento_cidade
                    WHERE tbl_treinamento_cidade.treinamento = tbl_treinamento.treinamento
                    AND tbl_treinamento_cidade.estado = tbl_posto.estado
                ) > 0
                OR (
                    SELECT count(tbl_treinamento_posto.treinamento_posto)
                    FROM tbl_treinamento_posto
                    WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                    AND tbl_treinamento_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
                ) > 0
            )
        GROUP BY 
            tbl_posto.nome,
            tbl_posto_fabrica.contato_email,
            tbl_treinamento.treinamento,
            tbl_treinamento.titulo,
            tbl_treinamento.descricao,
            tbl_treinamento.data_inicio,
            tbl_treinamento.data_fim,
            tbl_cidade.nome,
            tbl_cidade.estado,
            tbl_treinamento.prazo_inscricao,
            tbl_treinamento.vagas,
            tbl_treinamento.qtde_participante,
            tbl_posto_fabrica.posto,
            tbl_treinamento.local";
    $res2 = pg_query($con, $sql2);
    if (strlen(pg_last_error()) > 0) {
        $msg_erro["msg"][] =  "Erro ao pesquisar postos para lembrete";
    }
    if (pg_num_rows($res2) > 0){
        $dados_treinamento_avisar = pg_fetch_all($res2);
    }

    if (pg_num_rows($res) > 0 AND pg_num_rows($res2) == 0){
        $dados_geral = $dados_treinamento;
    }else if (pg_num_rows($res2) > 0 AND pg_num_rows($res) == 0){
        $dados_geral = $dados_treinamento_avisar;
    }else{
        $dados_geral = array_merge($dados_treinamento, $dados_treinamento_avisar);
    }

    if (count($dados_geral) > 0){
        foreach ($dados_geral as $key => $value) {
            $nome_posto         = $value['nome_posto'];
            $posto_email        = $value['posto_email'];
            $treinamento        = $value['treinamento'];
            $titulo             = $value['titulo'];
            $descricao          = $value['descricao'];
            $data_inicio        = $value['data_inicio'];
            $data_fim           = $value['data_fim'];
            $nome_cidade        = $value['nome_cidade'];
            $estado             = $value['estado'];
            $prazo_inscricao    = $value['prazo_inscricao'];
            $local_treinamento  = $value['local_treinamento'];
            $vagas              = $value['vagas'];
            $qtde_participante  = $value['qtde_participante'];
            $id_posto           = $value['id_posto'];

            $titulo_treinamento = "Treinamento: $titulo";
            $msg_email = "
                Data início: $data_inicio <br/>
                Data término: $data_fim <br/>
                Prazo inscrições até: $prazo_inscricao <br/>
                Quantidade de vagas: $vagas <br/>
                Quantidade de vagas por Posto: $qtde_participante <br/>
                Descrição: $descricao <br/>
                Cidade do treinamento: $nome_cidade <br/>
                Estado do treinamento: $estado <br/>
            ";
            
            if (!empty($posto_email)){
                try {
                    $comunicatorMirror->post($posto_email, utf8_encode("$titulo"), utf8_encode("$msg_email"), "smtp@posvenda");
                } catch (\Exception $e) {
                }
            }else{
                $msg_erro["msg"][] = "Email com informações do $titulo_treinamento não enviado para o posto $nome_posto. Posto sem email cadastrado";
            }
        }

        if (count($msg_erro["msg"]) > 0){
            if (count($msg_erro["msg"]) > 0) {
                $msg_email_erro = implode("<br />", $msg_erro["msg"]);
                try {
                    $comunicatorMirror->post('guilherme.monteiro@telecontrol.com.br', utf8_encode("ERRO NA ROTINA DE EMAIL TREINAMENTO"), utf8_encode("$msg_email_erro"), "smtp@posvenda");
                } catch (\Exception $e) {
                }        
            }
        }
    }

} catch (Exception $e) {
    echo $e->getMessage();
}

/**
 * Cron Término
 */
$phpCron->termino();
